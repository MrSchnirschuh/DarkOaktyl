<?php

namespace DarkOak\Http\Controllers\Api\Client;

use DarkOak\Http\Controllers\ApplicationApiController;
use DarkOak\Models\AutoScalingRule;
use DarkOak\Models\Server;
use DarkOak\Services\AutoScaling\AutoScalingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AutoScalingController extends ApplicationApiController
{
    private AutoScalingService $autoScalingService;

    public function __construct()
    {
        $this->autoScalingService = new AutoScalingService();
    }

    /**
     * Get auto-scaling rule for a server
     */
    public function show(Request $request, string $serverId): JsonResponse
    {
        $server = $this->getServer($request, $serverId);
        
        $rule = AutoScalingRule::where('server_id', $server->id)->first();

        if (!$rule) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'id' => $rule->id,
                'server_id' => $server->uuid,
                'enabled' => $rule->enabled,
                'cpu_threshold' => $rule->cpu_threshold,
                'memory_threshold' => $rule->memory_threshold,
                'disk_threshold' => $rule->disk_threshold,
                'scale_up_step' => $rule->scale_up_step,
                'scale_down_step' => $rule->scale_down_step,
                'min_memory' => $rule->min_memory,
                'max_memory' => $rule->max_memory,
                'scale_up_cooldown' => $rule->scale_up_cooldown,
                'scale_down_cooldown' => $rule->scale_down_cooldown,
                'last_scale_up_at' => $rule->last_scale_up_at?->toISOString(),
                'last_scale_down_at' => $rule->last_scale_down_at?->toISOString(),
                'created_at' => $rule->created_at->toISOString(),
                'updated_at' => $rule->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Create or update auto-scaling rule
     */
    public function update(Request $request, string $serverId): JsonResponse
    {
        $server = $this->getServer($request, $serverId);

        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'cpu_threshold' => 'required|integer|between:1,100',
            'memory_threshold' => 'required|integer|between:1,100',
            'disk_threshold' => 'required|integer|between:1,100',
            'scale_up_step' => 'required|integer|min:64',
            'scale_down_step' => 'required|integer|min:64',
            'min_memory' => 'required|integer|min:128',
            'max_memory' => 'required|integer|min:256|gte:min_memory',
            'scale_up_cooldown' => 'required|integer|min:1',
            'scale_down_cooldown' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rule = $this->autoScalingService->createOrUpdateRule(
            $server->id,
            $validator->validated()
        );

        return response()->json([
            'data' => [
                'id' => $rule->id,
                'server_id' => $server->uuid,
                'enabled' => $rule->enabled,
                'cpu_threshold' => $rule->cpu_threshold,
                'memory_threshold' => $rule->memory_threshold,
                'disk_threshold' => $rule->disk_threshold,
                'scale_up_step' => $rule->scale_up_step,
                'scale_down_step' => $rule->scale_down_step,
                'min_memory' => $rule->min_memory,
                'max_memory' => $rule->max_memory,
                'scale_up_cooldown' => $rule->scale_up_cooldown,
                'scale_down_cooldown' => $rule->scale_down_cooldown,
            ],
            'message' => 'Auto-scaling rule updated successfully',
        ]);
    }

    /**
     * Delete auto-scaling rule
     */
    public function destroy(Request $request, string $serverId): JsonResponse
    {
        $server = $this->getServer($request, $serverId);

        AutoScalingRule::where('server_id', $server->id)->delete();

        return response()->json([
            'message' => 'Auto-scaling rule deleted successfully',
        ]);
    }

    /**
     * Get auto-scaling history
     */
    public function history(Request $request, string $serverId): JsonResponse
    {
        $server = $this->getServer($request, $serverId);

        $rule = AutoScalingRule::where('server_id', $server->id)->first();

        if (!$rule) {
            return response()->json(['data' => []]);
        }

        $history = $this->autoScalingService->getHistory($rule->id, $request->input('limit', 50));

        return response()->json([
            'object' => 'list',
            'data' => $history,
        ]);
    }

    /**
     * Trigger manual scale evaluation
     */
    public function evaluate(Request $request, string $serverId): JsonResponse
    {
        $server = $this->getServer($request, $serverId);

        $result = $this->autoScalingService->evaluateScaling($server);

        if (!$result) {
            return response()->json([
                'message' => 'No scaling action required',
                'action' => null,
            ]);
        }

        return response()->json([
            'data' => [
                'action' => $result->action,
                'old_memory' => $result->old_memory,
                'new_memory' => $result->new_memory,
                'reason' => $result->reason,
                'created_at' => $result->created_at->toISOString(),
            ],
            'message' => 'Scaling action executed successfully',
        ]);
    }

    /**
     * Get server with permission check
     */
    private function getServer(Request $request, string $serverId): Server
    {
        $server = Server::where('uuid', $serverId)
            ->orWhere('uuidShort', $serverId)
            ->firstOrFail();

        // Check user has access to server
        if ($server->owner_id !== $request->user()->id) {
            // Check if user is a subuser
            $isSubuser = $server->subusers()
                ->where('user_id', $request->user()->id)
                ->exists();

            if (!$isSubuser) {
                abort(403, 'You do not have permission to manage this server.');
            }
        }

        return $server;
    }
}