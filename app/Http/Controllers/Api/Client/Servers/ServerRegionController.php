<?php

namespace DarkOak\Http\Controllers\Api\Client\Servers;

use DarkOak\Models\Allocation;
use DarkOak\Models\Node;
use DarkOak\Models\Region;
use DarkOak\Models\Server;
use Illuminate\Http\Request;
use DarkOak\Models\Permission;
use DarkOak\Exceptions\DisplayException;
use DarkOak\Services\Servers\ServerUpdateService;
use DarkOak\Transformers\Api\Client\ServerTransformer;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;
use DarkOak\Http\Requests\Api\Client\Servers\GetServerRequest;

class ServerRegionController extends ClientApiController
{
    /**
     * ServerRegionController constructor.
     */
    public function __construct(
        private ServerUpdateService $updateService
    ) {
        parent::__construct();
    }

    /**
     * Get available regions for server transfer.
     */
    public function availableRegions(GetServerRequest $request, Server $server): array
    {
        // Check if user has permission to transfer this server
        if (!$request->user()->can(Permission::ACTION_SETTINGS_RENAME, $server)) {
            throw new DisplayException('You do not have permission to transfer this server.');
        }

        // Get all active regions except current
        $currentRegionId = $server->node->region_id;
        
        $regions = Region::active()
            ->where('id', '!=', $currentRegionId)
            ->with(['nodes' => function ($query) use ($server) {
                // Only get nodes that can accommodate this server
                $query->where('public', true)
                    ->where('maintenance_mode', false)
                    ->where('deployable', true);
            }])
            ->get()
            ->filter(function ($region) {
                // Only return regions with available nodes
                return $region->nodes->isNotEmpty();
            })
            ->values();

        return [
            'data' => $regions->map(function (Region $region) {
                return [
                    'id' => $region->id,
                    'uuid' => $region->uuid,
                    'name' => $region->name,
                    'code' => $region->code,
                    'display_name' => $region->display_name,
                    'description' => $region->description,
                    'timezone' => $region->timezone,
                    'coordinates' => $region->coordinates,
                    'available_nodes' => $region->nodes->count(),
                ];
            }),
        ];
    }

    /**
     * Transfer server to a different region.
     */
    public function transfer(Request $request, Server $server): array
    {
        // Check permission
        if (!$request->user()->can(Permission::ACTION_SETTINGS_RENAME, $server)) {
            throw new DisplayException('You do not have permission to transfer this server.');
        }

        // Validate server is not already transferring
        if ($server->transfer) {
            throw new DisplayException('Server is already being transferred.');
        }

        // Validate request
        $validated = $request->validate([
            'region_id' => 'required|integer|exists:regions,id',
            'node_id' => 'nullable|integer|exists:nodes,id',
        ]);

        $regionId = $validated['region_id'];
        $nodeId = $validated['node_id'] ?? null;

        // Get target region
        $region = Region::findOrFail($regionId);

        // If no specific node requested, find best node in region
        if (!$nodeId) {
            $node = Node::where('region_id', $regionId)
                ->where('public', true)
                ->where('maintenance_mode', false)
                ->where('deployable', true)
                ->first();

            if (!$node) {
                throw new DisplayException('No available nodes in this region.');
            }
        } else {
            $node = Node::where('id', $nodeId)
                ->where('region_id', $regionId)
                ->where('public', true)
                ->where('maintenance_mode', false)
                ->first();

            if (!$node) {
                throw new DisplayException('Selected node is not available.');
            }
        }

        // Check if node can accommodate server
        if (!$node->isViable($server->memory, $server->disk)) {
            throw new DisplayException('Target node does not have sufficient resources.');
        }

        // Check if server is in a transferable state
        try {
            $server->validateTransferState();
        } catch (\Exception $e) {
            throw new DisplayException('Server cannot be transferred in its current state.');
        }

        // Get allocations for new node
        $allocation = $node->allocations()->whereNull('server_id')->first();
        if (!$allocation) {
            throw new DisplayException('No available allocations on target node.');
        }

        assert($allocation instanceof Allocation);

        // Create server transfer
        $transfer = new \DarkOak\Models\ServerTransfer();
        $transfer->server_id = $server->id;
        $transfer->old_node = $server->node_id;
        assert($node instanceof Node);
        $transfer->new_node = $node->id;
        $transfer->old_allocation = $server->allocation_id;
        $transfer->new_allocation = $allocation->id;
        $transfer->save();

        // Update server status - using installing as transfer indicator
        // The daemon will handle the actual transfer
        $server->save();

        return $this->fractal->item($server)
            ->transformWith(ServerTransformer::class)
            ->addMeta([
                'transfer_status' => 'pending',
                'transfer_region' => $region->display_name,
            ])
            ->toArray();
    }

    /**
     * Get transfer status.
     */
    public function status(GetServerRequest $request, Server $server): array
    {
        if (!$request->user()->can(Permission::ACTION_SETTINGS_RENAME, $server)) {
            throw new DisplayException('You do not have permission to view transfer status.');
        }

        $transfer = $server->transfer;

        if (!$transfer) {
            return [
                'data' => [
                    'is_transferring' => false,
                    'status' => null,
                ],
            ];
        }

        return [
            'data' => [
                'is_transferring' => true,
                'status' => $transfer->status ?? 'pending',
                'old_node' => Node::find($transfer->old_node)?->name ?? 'Unknown',
                'new_node' => Node::find($transfer->new_node)?->name ?? 'Unknown',
                'created_at' => $transfer->created_at?->toIso8601String(),
            ],
        ];
    }
}
