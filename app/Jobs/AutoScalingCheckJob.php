<?php

namespace DarkOak\Jobs;

use DarkOak\Models\Server;
use DarkOak\Models\AutoScalingRule;
use Illuminate\Support\Facades\Log;
use DarkOak\Models\AutoScalingHistory;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use DarkOak\Repositories\Eloquent\ServerRepository;
use DarkOak\Repositories\Wings\DaemonServerRepository;
use DarkOak\Exceptions\Http\Connection\DaemonConnectionException;

class AutoScalingCheckJob extends Job implements ShouldQueue
{
    use InteractsWithQueue;
    use SerializesModels;

    /**
     * AutoScalingCheckJob constructor.
     */
    public function __construct(public ?Server $server = null)
    {
        $this->queue = 'standard';
    }

    /**
     * Execute the job.
     */
    public function handle(
        DaemonServerRepository $daemonRepository,
        ServerRepository $serverRepository,
    ): void {
        // If no specific server, check all enabled rules
        if (!$this->server) {
            $this->checkAllServers($daemonRepository, $serverRepository);

            return;
        }

        // Check specific server
        $this->checkServer($this->server, $daemonRepository, $serverRepository);
    }

    /**
     * Check all servers with enabled auto-scaling.
     */
    private function checkAllServers(
        DaemonServerRepository $daemonRepository,
        ServerRepository $serverRepository,
    ): void {
        $rules = AutoScalingRule::where('enabled', true)->get();

        foreach ($rules as $rule) {
            try {
                $server = $rule->server;
                if (!$server || $server->isSuspended() || !$server->isInstalled()) {
                    continue;
                }

                $this->checkServer($server, $daemonRepository, $serverRepository);
            } catch (\Exception $e) {
                Log::error('Auto-scaling check failed for server', [
                    'server_id' => $rule->server_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check a specific server's metrics and scale if needed.
     */
    private function checkServer(
        Server $server,
        DaemonServerRepository $daemonRepository,
        ServerRepository $serverRepository,
    ): void {
        $rule = AutoScalingRule::where('server_id', $server->id)->first();

        if (!$rule || !$rule->enabled) {
            return;
        }

        // Check if in cooldown
        if ($rule->isInCooldown()) {
            Log::debug('Auto-scaling in cooldown', [
                'server_id' => $server->id,
                'remaining_minutes' => $rule->getRemainingCooldownMinutes(),
            ]);

            return;
        }

        // Get current metrics from daemon
        try {
            $daemonRepository->setServer($server);
            $details = $daemonRepository->getDetails();
        } catch (DaemonConnectionException $e) {
            Log::error('Failed to get server details from daemon', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        // Extract metrics
        $metrics = $this->extractMetrics($details);

        // Determine if scaling is needed
        $scaleAction = $this->determineScaleAction($rule, $metrics, $server);

        if ($scaleAction['action'] === 'none') {
            return;
        }

        // Execute scaling
        $this->executeScaling($server, $rule, $scaleAction, $metrics, $serverRepository);
    }

    /**
     * Extract metrics from daemon response.
     */
    private function extractMetrics(array $details): array
    {
        $resources = $details['resources'] ?? [];

        return [
            'cpu' => $resources['cpu_absolute'] ?? 0,
            'memory_used' => $resources['memory_bytes'] ?? 0,
            'memory_limit' => $resources['memory_limit_bytes'] ?? 0,
            'disk_used' => $resources['disk_bytes'] ?? 0,
            'disk_limit' => $resources['disk_limit_bytes'] ?? 0,
            'uptime' => $resources['uptime'] ?? 0,
        ];
    }

    /**
     * Determine if scaling up or down is needed.
     */
    private function determineScaleAction(AutoScalingRule $rule, array $metrics, Server $server): array
    {
        $cpuPercent = $metrics['cpu'];
        $ramPercent = $metrics['memory_limit'] > 0
            ? ($metrics['memory_used'] / $metrics['memory_limit']) * 100
            : 0;
        $diskPercent = $metrics['disk_limit'] > 0
            ? ($metrics['disk_used'] / $metrics['disk_limit']) * 100
            : 0;

        // Get current server limits
        $currentMemory = $server->memory;
        $currentCpu = $server->cpu;
        $currentDisk = $server->disk;

        // Check for scale up conditions
        $scaleUpTriggers = [];

        if ($cpuPercent >= $rule->cpu_threshold_up) {
            $scaleUpTriggers[] = 'cpu';
        }
        if ($ramPercent >= $rule->ram_threshold_up) {
            $scaleUpTriggers[] = 'ram';
        }
        if ($diskPercent >= $rule->disk_threshold_up) {
            $scaleUpTriggers[] = 'disk';
        }

        if (!empty($scaleUpTriggers)) {
            // Calculate new limits
            $memoryIncrease = min($rule->scale_up_step, $rule->scale_up_limit);
            $newMemory = $currentMemory + $memoryIncrease;

            // Check if we're within limits
            if ($memoryIncrease > 0 && $newMemory <= $this->getUserMemoryLimit($server)) {
                return [
                    'action' => 'scale_up',
                    'trigger' => implode(',', $scaleUpTriggers),
                    'resource' => in_array('ram', $scaleUpTriggers) ? 'memory' : 'cpu',
                    'new_memory' => $newMemory,
                    'new_cpu' => $currentCpu,
                    'new_disk' => $currentDisk,
                    'reason' => sprintf(
                        'Scale up triggered by %s. CPU: %.1f%%, RAM: %.1f%%, Disk: %.1f%%',
                        implode(', ', $scaleUpTriggers),
                        $cpuPercent,
                        $ramPercent,
                        $diskPercent
                    ),
                ];
            }
        }

        // Check for scale down conditions
        $scaleDownTriggers = [];

        if ($cpuPercent <= $rule->cpu_threshold_down) {
            $scaleDownTriggers[] = 'cpu';
        }
        if ($ramPercent <= $rule->ram_threshold_down) {
            $scaleDownTriggers[] = 'ram';
        }
        if ($diskPercent <= $rule->disk_threshold_down) {
            $scaleDownTriggers[] = 'disk';
        }

        if (!empty($scaleDownTriggers)) {
            // Calculate new limits
            $memoryDecrease = min($rule->scale_down_step, $rule->scale_up_limit);
            $newMemory = max($currentMemory - $memoryDecrease, $rule->scale_down_limit);

            // Only scale down if we're above minimum
            if ($newMemory < $currentMemory && $newMemory >= $rule->scale_down_limit) {
                return [
                    'action' => 'scale_down',
                    'trigger' => implode(',', $scaleDownTriggers),
                    'resource' => in_array('ram', $scaleDownTriggers) ? 'memory' : 'cpu',
                    'new_memory' => $newMemory,
                    'new_cpu' => $currentCpu,
                    'new_disk' => $currentDisk,
                    'reason' => sprintf(
                        'Scale down triggered by %s. CPU: %.1f%%, RAM: %.1f%%, Disk: %.1f%%',
                        implode(', ', $scaleDownTriggers),
                        $cpuPercent,
                        $ramPercent,
                        $diskPercent
                    ),
                ];
            }
        }

        return ['action' => 'none'];
    }

    /**
     * Get user-defined memory limit for a server.
     */
    private function getUserMemoryLimit(Server $server): int
    {
        // Get the user's product limit if applicable
        if ($server->billing_product_id) {
            $product = $server->product;
            if ($product) {
                return $product->memory ?? PHP_INT_MAX;
            }
        }

        // Default: allow up to 2x the current allocation
        return $server->memory * 2;
    }

    /**
     * Execute the scaling action.
     */
    private function executeScaling(
        Server $server,
        AutoScalingRule $rule,
        array $scaleAction,
        array $metrics,
        ServerRepository $serverRepository,
    ): void {
        $oldMemory = $server->memory;
        $oldCpu = $server->cpu;
        $oldDisk = $server->disk;

        try {
            // Update server resources
            $serverRepository->update($server->id, [
                'memory' => $scaleAction['new_memory'],
                'cpu' => $scaleAction['new_cpu'],
                'disk' => $scaleAction['new_disk'],
            ]);

            // Sync with daemon
            $server->refresh();
            $daemonRepository = app(DaemonServerRepository::class);
            $daemonRepository->setServer($server);
            $daemonRepository->sync();

            // Update rule timestamp
            if ($scaleAction['action'] === 'scale_up') {
                $rule->markScaleUp();
            } else {
                $rule->markScaleDown();
            }

            // Log history
            AutoScalingHistory::create([
                'server_id' => $server->id,
                'auto_scaling_rule_id' => $rule->id,
                'action' => $scaleAction['action'] === 'scale_up'
                    ? AutoScalingHistory::ACTION_SCALE_UP
                    : AutoScalingHistory::ACTION_SCALE_DOWN,
                'triggered_by' => $this->mapTriggerToConstant($scaleAction['trigger']),
                'cpu_usage_before' => (int) $metrics['cpu'],
                'ram_usage_mb_before' => (int) ($metrics['memory_used'] / 1024 / 1024),
                'disk_usage_mb_before' => (int) ($metrics['disk_used'] / 1024 / 1024),
                'memory_limit_before' => $oldMemory,
                'memory_limit_after' => $scaleAction['new_memory'],
                'cpu_limit_before' => $oldCpu,
                'cpu_limit_after' => $scaleAction['new_cpu'],
                'disk_limit_before' => $oldDisk,
                'disk_limit_after' => $scaleAction['new_disk'],
                'reason' => $scaleAction['reason'],
                'status' => AutoScalingHistory::STATUS_SUCCESS,
            ]);

            Log::info('Auto-scaling executed successfully', [
                'server_id' => $server->id,
                'action' => $scaleAction['action'],
                'old_memory' => $oldMemory,
                'new_memory' => $scaleAction['new_memory'],
            ]);
        } catch (\Exception $e) {
            // Log failure
            AutoScalingHistory::create([
                'server_id' => $server->id,
                'auto_scaling_rule_id' => $rule->id,
                'action' => $scaleAction['action'] === 'scale_up'
                    ? AutoScalingHistory::ACTION_SCALE_UP
                    : AutoScalingHistory::ACTION_SCALE_DOWN,
                'triggered_by' => $this->mapTriggerToConstant($scaleAction['trigger']),
                'cpu_usage_before' => (int) $metrics['cpu'],
                'ram_usage_mb_before' => (int) ($metrics['memory_used'] / 1024 / 1024),
                'disk_usage_mb_before' => (int) ($metrics['disk_used'] / 1024 / 1024),
                'memory_limit_before' => $oldMemory,
                'cpu_limit_before' => $oldCpu,
                'disk_limit_before' => $oldDisk,
                'reason' => $scaleAction['reason'],
                'status' => AutoScalingHistory::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Auto-scaling execution failed', [
                'server_id' => $server->id,
                'action' => $scaleAction['action'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Map trigger string to constant.
     */
    private function mapTriggerToConstant(string $trigger): string
    {
        if (str_contains($trigger, 'cpu')) {
            return AutoScalingHistory::TRIGGER_CPU;
        }
        if (str_contains($trigger, 'ram')) {
            return AutoScalingHistory::TRIGGER_RAM;
        }
        if (str_contains($trigger, 'disk')) {
            return AutoScalingHistory::TRIGGER_DISK;
        }

        return AutoScalingHistory::TRIGGER_MANUAL;
    }
}
