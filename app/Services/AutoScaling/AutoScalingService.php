<?php

namespace DarkOak\Services\AutoScaling;

use DarkOak\Models\AutoScalingRule;
use DarkOak\Models\AutoScalingHistory;
use DarkOak\Models\Server;
use DarkOak\Services\Servers\ServerConfigurationService;
use DarkOak\Services\PushNotifications\PushNotificationService;
use Illuminate\Support\Facades\Log;

class AutoScalingService
{
    private ServerConfigurationService $configService;
    private PushNotificationService $pushService;

    public function __construct()
    {
        $this->configService = new ServerConfigurationService();
        $this->pushService = new PushNotificationService();
    }

    /**
     * Evaluate auto-scaling for a server
     */
    public function evaluateScaling(Server $server): ?AutoScalingHistory
    {
        $rule = AutoScalingRule::where('server_id', $server->id)->first();
        
        if (!$rule || !$rule->enabled) {
            return null;
        }

        // Get current server stats from Wings
        $stats = $this->fetchServerStats($server);
        
        if (!$stats) {
            Log::warning('Could not fetch server stats for auto-scaling', [
                'server_id' => $server->id,
            ]);
            return null;
        }

        // Determine scaling action
        $action = $this->determineScalingAction($rule, $stats);
        
        if ($action === null) {
            return null;
        }

        // Execute scaling
        return $this->executeScaling($server, $rule, $action, $stats);
    }

    /**
     * Fetch current server stats from Wings
     */
    private function fetchServerStats(Server $server): ?array
    {
        try {
            $response = $server->node->guzzleClient()->get(
                sprintf('/api/servers/%s/resources', $server->uuid)
            );

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'cpu' => $data['resources']['cpu_absolute'] ?? 0,
                'memory' => $data['resources']['memory_bytes'] ?? 0,
                'memory_limit' => $data['resources']['memory_limit'] ?? ($server->memory * 1024 * 1024),
                'disk' => $data['resources']['disk_bytes'] ?? 0,
                'disk_limit' => $data['resources']['disk_limit'] ?? ($server->disk * 1024 * 1024),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch server stats', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Determine if scaling is needed
     */
    private function determineScalingAction(AutoScalingRule $rule, array $stats): ?string
    {
        $memoryUsagePercent = ($stats['memory'] / $stats['memory_limit']) * 100;
        $cpuUsagePercent = $stats['cpu'];
        $diskUsagePercent = ($stats['disk'] / max($stats['disk_limit'], 1)) * 100;

        // Check for scale up
        if ($memoryUsagePercent >= $rule->memory_threshold 
            || $cpuUsagePercent >= $rule->cpu_threshold
            || $diskUsagePercent >= $rule->disk_threshold) {
            
            if ($rule->canScaleUp() && !$rule->isAtMaxMemory($rule->server->memory)) {
                return 'up';
            }
        }

        // Check for scale down
        $scaleDownThreshold = max(
            $rule->memory_threshold * 0.5,
            $rule->memory_threshold - 20
        );

        if ($memoryUsagePercent < $scaleDownThreshold && $cpuUsagePercent < $rule->cpu_threshold * 0.5) {
            if ($rule->canScaleDown() && !$rule->isAtMinMemory($rule->server->memory)) {
                return 'down';
            }
        }

        return null;
    }

    /**
     * Execute scaling action
     */
    private function executeScaling(Server $server, AutoScalingRule $rule, string $action, array $stats): AutoScalingHistory
    {
        $currentMemory = $server->memory;
        $newMemory = $action === 'up' 
            ? $rule->calculateScaleUp($currentMemory)
            : $rule->calculateScaleDown($currentMemory);

        // Create history entry
        $history = AutoScalingHistory::create([
            'auto_scaling_rule_id' => $rule->id,
            'action' => $action,
            'old_memory' => $currentMemory,
            'new_memory' => $newMemory,
            'cpu_usage' => $stats['cpu'],
            'memory_usage' => $stats['memory'],
            'memory_usage_percent' => ($stats['memory'] / $stats['memory_limit']) * 100,
            'disk_usage' => $stats['disk'],
            'reason' => "Auto-scaled $action: CPU at {$stats['cpu']}%, Memory at " . round(($stats['memory'] / $stats['memory_limit']) * 100, 1) . '%',
        ]);

        try {
            // Update server memory
            $server->update(['memory' => $newMemory]);

            // Sync with Wings
            $this->configService->handleUpdates($server);

            // Update rule timestamps
            if ($action === 'up') {
                $rule->update(['last_scale_up_at' => now()]);
            } else {
                $rule->update(['last_scale_down_at' => now()]);
            }

            // Notify user
            $this->notifyUser($server, $action, $currentMemory, $newMemory);

            Log::info('Auto-scaling executed', [
                'server_id' => $server->id,
                'action' => $action,
                'old_memory' => $currentMemory,
                'new_memory' => $newMemory,
            ]);

        } catch (\Exception $e) {
            $history->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Auto-scaling failed', [
                'server_id' => $server->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }

        return $history;
    }

    /**
     * Send notification to user
     */
    private function notifyUser(Server $server, string $action, int $oldMemory, int $newMemory): void
    {
        $actionText = $action === 'up' ? 'increased' : 'decreased';
        $memoryDiff = $action === 'up' ? $newMemory - $oldMemory : $oldMemory - $newMemory;

        $this->pushService->notifyUser($server->user, 'server.auto_scaling', [
            'title' => 'Server Auto-Scaled',
            'body' => "Your server {$server->name} has been {$actionText} from {$oldMemory}MB to {$newMemory}MB",
            'data' => [
                'server_id' => $server->id,
                'action' => $action,
                'old_memory' => $oldMemory,
                'new_memory' => $newMemory,
            ],
        ]);
    }

    /**
     * Create or update auto-scaling rule for server
     */
    public function createOrUpdateRule(int $serverId, array $data): AutoScalingRule
    {
        $rule = AutoScalingRule::firstOrNew(['server_id' => $serverId]);
        
        $rule->fill([
            'cpu_threshold' => $data['cpu_threshold'] ?? 80,
            'memory_threshold' => $data['memory_threshold'] ?? 85,
            'disk_threshold' => $data['disk_threshold'] ?? 90,
            'scale_up_step' => $data['scale_up_step'] ?? 512,
            'scale_down_step' => $data['scale_down_step'] ?? 512,
            'min_memory' => $data['min_memory'] ?? 512,
            'max_memory' => $data['max_memory'] ?? 8192,
            'scale_up_cooldown' => $data['scale_up_cooldown'] ?? 10,
            'scale_down_cooldown' => $data['scale_down_cooldown'] ?? 30,
            'enabled' => $data['enabled'] ?? false,
        ]);

        $rule->save();

        return $rule;
    }

    /**
     * Get scaling history for a rule
     */
    public function getHistory(int $ruleId, int $limit = 50): array
    {
        return AutoScalingHistory::where('auto_scaling_rule_id', $ruleId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}