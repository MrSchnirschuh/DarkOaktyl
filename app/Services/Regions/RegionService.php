<?php

namespace DarkOak\Services\Regions;

use DarkOak\Models\Region;
use DarkOak\Models\Node;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RegionService
{
    /**
     * Get all active regions
     */
    public function getActiveRegions(): array
    {
        return Cache::remember('regions.active', 3600, function () {
            return Region::active()
                ->withCount('nodes')
                ->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->get()
                ->map(fn ($region) => $this->formatRegion($region))
                ->toArray();
        });
    }

    /**
     * Get default region
     */
    public function getDefaultRegion(): ?Region
    {
        return Region::where('is_default', true)->first();
    }

    /**
     * Get region by code
     */
    public function getRegionByCode(string $code): ?Region
    {
        return Region::where('code', $code)->first();
    }

    /**
     * Get nodes for a region
     */
    public function getRegionNodes(int $regionId): array
    {
        return Node::where('region_id', $regionId)
            ->where('public', true)
            ->where('maintenance_mode', false)
            ->get()
            ->map(fn ($node) => [
                'id' => $node->uuid,
                'name' => $node->name,
                'description' => $node->description,
                'location_id' => $node->location_id,
            ])
            ->toArray();
    }

    /**
     * Get region statistics
     */
    public function getRegionStats(int $regionId): array
    {
        $nodes = Node::where('region_id', $regionId)->get();

        return [
            'total_nodes' => $nodes->count(),
            'online_nodes' => $nodes->where('isOnline', true)->count(),
            'total_servers' => $nodes->sum(fn ($n) => $n->servers()->count()),
            'total_allocations' => $nodes->sum(fn ($n) => $n->allocations()->count()),
            'available_allocations' => $nodes->sum(fn ($n) => $n->allocations()->whereNull('server_id')->count()),
        ];
    }

    /**
     * Select best region for deployment
     */
    public function selectBestRegion(?string $preferredRegion = null): ?Region
    {
        // If preferred region specified, try it first
        if ($preferredRegion) {
            $region = $this->getRegionByCode($preferredRegion);
            if ($region && $region->is_active && $this->hasAvailableCapacity($region)) {
                return $region;
            }
        }

        // Find region with most available capacity
        $regions = Region::active()
            ->with('nodes')
            ->get();

        $bestRegion = null;
        $maxCapacity = 0;

        foreach ($regions as $region) {
            $capacity = $this->calculateRegionCapacity($region);
            if ($capacity > $maxCapacity) {
                $maxCapacity = $capacity;
                $bestRegion = $region;
            }
        }

        // If no region with capacity, fall back to default
        return $bestRegion ?? $this->getDefaultRegion();
    }

    /**
     * Check if region has available capacity
     */
    private function hasAvailableCapacity(Region $region): bool
    {
        $capacity = $this->calculateRegionCapacity($region);
        return $capacity > 0;
    }

    /**
     * Calculate region capacity score
     */
    private function calculateRegionCapacity(Region $region): int
    {
        return $region->nodes
            ->where('public', true)
            ->where('maintenance_mode', false)
            ->sum(fn ($node) => $node->allocations()->whereNull('server_id')->count());
    }

    /**
     * Format region for API response
     */
    private function formatRegion(Region $region): array
    {
        return [
            'id' => $region->uuid,
            'name' => $region->name,
            'code' => $region->code,
            'display_name' => $region->display_name,
            'description' => $region->description,
            'timezone' => $region->timezone,
            'coordinates' => $region->coordinates,
            'is_default' => $region->is_default,
            'is_active' => $region->is_active,
            'node_count' => $region->nodes_count,
            'ping_endpoint' => $region->ping_endpoint,
        ];
    }

    /**
     * Create new region
     */
    public function createRegion(array $data): Region
    {
        $region = Region::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'display_name' => $data['display_name'] ?? $data['name'],
            'description' => $data['description'] ?? null,
            'timezone' => $data['timezone'] ?? 'UTC',
            'coordinates' => $data['coordinates'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_default' => $data['is_default'] ?? false,
            'ping_endpoint' => $data['ping_endpoint'] ?? null,
        ]);

        $this->clearCache();

        Log::info('Region created', [
            'region_id' => $region->id,
            'name' => $region->name,
        ]);

        return $region;
    }

    /**
     * Update region
     */
    public function updateRegion(Region $region, array $data): Region
    {
        $region->update([
            'name' => $data['name'] ?? $region->name,
            'display_name' => $data['display_name'] ?? $region->display_name,
            'description' => $data['description'] ?? $region->description,
            'timezone' => $data['timezone'] ?? $region->timezone,
            'coordinates' => $data['coordinates'] ?? $region->coordinates,
            'is_active' => $data['is_active'] ?? $region->is_active,
            'ping_endpoint' => $data['ping_endpoint'] ?? $region->ping_endpoint,
        ]);

        $this->clearCache();

        return $region;
    }

    /**
     * Delete region
     */
    public function deleteRegion(Region $region): bool
    {
        // Check if region has nodes
        if ($region->nodes()->exists()) {
            throw new \Exception('Cannot delete region with assigned nodes');
        }

        // Cannot delete default region
        if ($region->is_default) {
            throw new \Exception('Cannot delete default region');
        }

        $region->delete();
        $this->clearCache();

        return true;
    }

    /**
     * Set region as default
     */
    public function setAsDefault(Region $region): void
    {
        // Model handles making others non-default via saving event
        $region->update(['is_default' => true]);
        $this->clearCache();
    }

    /**
     * Clear region cache
     */
    private function clearCache(): void
    {
        Cache::forget('regions.active');
    }

    /**
     * Assign node to region
     */
    public function assignNodeToRegion(int $nodeId, int $regionId): void
    {
        $node = Node::findOrFail($nodeId);
        $node->update(['region_id' => $regionId]);
        
        $this->clearCache();
    }

    /**
     * Get latency estimate for region
     */
    public function getLatencyEstimate(string $regionCode): ?int
    {
        $region = $this->getRegionByCode($regionCode);
        
        if (!$region || !$region->ping_endpoint) {
            return null;
        }

        // Placeholder: would implement actual latency check
        // Could use JavaScript ping from client side
        return null;
    }
}