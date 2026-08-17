<?php

namespace DarkOak\Http\Controllers\Api\Client\Servers;

use DarkOak\Models\Server;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use DarkOak\Models\AutoScalingRule;
use DarkOak\Models\AutoScalingHistory;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;
use DarkOak\Http\Requests\Api\Client\Servers\AutoScaling\UpdateAutoScalingRequest;

class AutoScalingController extends ClientApiController
{
    /**
     * Get the auto-scaling configuration for a server.
     */
    public function show(Server $server): JsonResponse
    {
        $rule = AutoScalingRule::firstOrCreate(
            ['server_id' => $server->id],
            [
                'enabled' => false,
                'cpu_threshold_up' => 80,
                'cpu_threshold_down' => 20,
                'ram_threshold_up' => 85,
                'ram_threshold_down' => 25,
                'disk_threshold_up' => 90,
                'disk_threshold_down' => 30,
                'scale_up_limit' => 2048,
                'scale_down_limit' => 512,
                'scale_up_step' => 512,
                'scale_down_step' => 256,
                'cooldown_minutes' => 10,
            ]
        );

        return new JsonResponse([
            'data' => [
                'id' => $rule->id,
                'enabled' => $rule->enabled,
                'thresholds' => [
                    'cpu' => [
                        'up' => $rule->cpu_threshold_up,
                        'down' => $rule->cpu_threshold_down,
                    ],
                    'ram' => [
                        'up' => $rule->ram_threshold_up,
                        'down' => $rule->ram_threshold_down,
                    ],
                    'disk' => [
                        'up' => $rule->disk_threshold_up,
                        'down' => $rule->disk_threshold_down,
                    ],
                ],
                'limits' => [
                    'scale_up' => $rule->scale_up_limit,
                    'scale_down' => $rule->scale_down_limit,
                ],
                'steps' => [
                    'up' => $rule->scale_up_step,
                    'down' => $rule->scale_down_step,
                ],
                'cooldown_minutes' => $rule->cooldown_minutes,
                'is_in_cooldown' => $rule->isInCooldown(),
                'remaining_cooldown_minutes' => $rule->getRemainingCooldownMinutes(),
                'last_scale_up_at' => $rule->last_scale_up_at,
                'last_scale_down_at' => $rule->last_scale_down_at,
            ],
        ]);
    }

    /**
     * Update the auto-scaling configuration for a server.
     */
    public function update(UpdateAutoScalingRequest $request, Server $server): JsonResponse
    {
        $rule = AutoScalingRule::firstOrNew(['server_id' => $server->id]);

        $originalEnabled = $rule->enabled;

        $rule->fill([
            'enabled' => $request->input('enabled', $rule->enabled ?? false),
            'cpu_threshold_up' => $request->input('cpu_threshold_up', $rule->cpu_threshold_up ?? 80),
            'cpu_threshold_down' => $request->input('cpu_threshold_down', $rule->cpu_threshold_down ?? 20),
            'ram_threshold_up' => $request->input('ram_threshold_up', $rule->ram_threshold_up ?? 85),
            'ram_threshold_down' => $request->input('ram_threshold_down', $rule->ram_threshold_down ?? 25),
            'disk_threshold_up' => $request->input('disk_threshold_up', $rule->disk_threshold_up ?? 90),
            'disk_threshold_down' => $request->input('disk_threshold_down', $rule->disk_threshold_down ?? 30),
            'scale_up_limit' => $request->input('scale_up_limit', $rule->scale_up_limit ?? 2048),
            'scale_down_limit' => $request->input('scale_down_limit', $rule->scale_down_limit ?? 512),
            'scale_up_step' => $request->input('scale_up_step', $rule->scale_up_step ?? 512),
            'scale_down_step' => $request->input('scale_down_step', $rule->scale_down_step ?? 256),
            'cooldown_minutes' => $request->input('cooldown_minutes', $rule->cooldown_minutes ?? 10),
        ]);

        $rule->save();

        // Log activity if enabled status changed
        if ($originalEnabled !== $rule->enabled) {
            Activity::event('server:auto-scaling.' . ($rule->enabled ? 'enabled' : 'disabled'))
                ->property(['server_id' => $server->id])
                ->log();
        }

        Activity::event('server:auto-scaling.updated')
            ->property(['server_id' => $server->id])
            ->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get the auto-scaling history for a server.
     */
    public function history(Server $server): JsonResponse
    {
        $history = AutoScalingHistory::where('server_id', $server->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        $data = $history->map(function ($entry) {
            return [
                'id' => $entry->id,
                'action' => $entry->action,
                'action_label' => $entry->getActionLabel(),
                'triggered_by' => $entry->triggered_by,
                'trigger_label' => $entry->getTriggerLabel(),
                'metrics' => [
                    'before' => [
                        'cpu' => $entry->cpu_usage_before,
                        'ram' => $entry->ram_usage_mb_before,
                        'disk' => $entry->disk_usage_mb_before,
                    ],
                    'after' => [
                        'cpu' => $entry->cpu_usage_after,
                        'ram' => $entry->ram_usage_mb_after,
                        'disk' => $entry->disk_usage_after,
                    ],
                ],
                'limits' => [
                    'before' => [
                        'memory' => $entry->memory_limit_before,
                        'cpu' => $entry->cpu_limit_before,
                        'disk' => $entry->disk_limit_before,
                    ],
                    'after' => [
                        'memory' => $entry->memory_limit_after,
                        'cpu' => $entry->cpu_limit_after,
                        'disk' => $entry->disk_limit_after,
                    ],
                ],
                'reason' => $entry->reason,
                'status' => $entry->status,
                'status_label' => $entry->getStatusLabel(),
                'error_message' => $entry->error_message,
                'created_at' => $entry->created_at,
            ];
        });

        return new JsonResponse([
            'data' => $data,
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    /**
     * Trigger manual scaling action.
     */
    public function manualScale(Server $server): JsonResponse
    {
        $rule = AutoScalingRule::where('server_id', $server->id)->first();

        if (!$rule || !$rule->enabled) {
            return new JsonResponse([
                'error' => 'Auto-scaling is not enabled for this server.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Create history entry for manual trigger
        AutoScalingHistory::create([
            'server_id' => $server->id,
            'auto_scaling_rule_id' => $rule->id,
            'action' => AutoScalingHistory::ACTION_NO_ACTION,
            'triggered_by' => AutoScalingHistory::TRIGGER_MANUAL,
            'reason' => 'Manual scaling check triggered',
            'status' => AutoScalingHistory::STATUS_SUCCESS,
        ]);

        // Dispatch the check job
        dispatch(new \DarkOak\Jobs\AutoScalingCheckJob($server));

        Activity::event('server:auto-scaling.manual-trigger')
            ->property(['server_id' => $server->id])
            ->log();

        return new JsonResponse([
            'message' => 'Scaling check has been queued.',
        ]);
    }
}
