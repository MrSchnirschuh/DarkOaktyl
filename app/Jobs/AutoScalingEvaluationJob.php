<?php

namespace DarkOak\Jobs;

use DarkOak\Models\AutoScalingRule;
use DarkOak\Services\AutoScaling\AutoScalingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoScalingEvaluationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $serverId;

    public function __construct(int $serverId)
    {
        $this->serverId = $serverId;
    }

    public function handle(): void
    {
        $rule = AutoScalingRule::where('server_id', $this->serverId)
            ->where('enabled', true)
            ->first();

        if (!$rule) {
            return;
        }

        $service = new AutoScalingService();
        $result = $service->evaluateScaling($rule->server);

        if ($result) {
            Log::info('Auto-scaling evaluated by job', [
                'server_id' => $this->serverId,
                'action' => $result->action,
                'old_memory' => $result->old_memory,
                'new_memory' => $result->new_memory,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Auto-scaling evaluation job failed', [
            'server_id' => $this->serverId,
            'error' => $exception->getMessage(),
        ]);
    }
}
