<?php

namespace DarkOak\Http\Controllers\Api\Client;

use DarkOak\Http\Controllers\ApplicationApiController;
use DarkOak\Services\Regions\RegionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RegionController extends ApplicationApiController
{
    private RegionService $regionService;

    public function __construct()
    {
        $this->regionService = new RegionService();
    }

    /**
     * List all active regions
     */
    public function index(Request $request): JsonResponse
    {
        $regions = $this->regionService->getActiveRegions();

        return response()->json([
            'object' => 'list',
            'data' => $regions,
        ]);
    }

    /**
     * Get default region
     */
    public function default(Request $request): JsonResponse
    {
        $region = $this->regionService->getDefaultRegion();

        if (!$region) {
            return response()->json([
                'error' => 'No default region configured',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $region->uuid,
                'name' => $region->name,
                'code' => $region->code,
                'display_name' => $region->display_name,
                'timezone' => $region->timezone,
            ],
        ]);
    }

    /**
     * Get single region
     */
    public function show(Request $request, string $code): JsonResponse
    {
        $region = $this->regionService->getRegionByCode($code);

        if (!$region) {
            return response()->json([
                'error' => 'Region not found',
            ], 404);
        }

        $stats = $this->regionService->getRegionStats($region->id);
        $nodes = $this->regionService->getRegionNodes($region->id);

        return response()->json([
            'data' => [
                'id' => $region->uuid,
                'name' => $region->name,
                'code' => $region->code,
                'display_name' => $region->display_name,
                'description' => $region->description,
                'timezone' => $region->timezone,
                'coordinates' => $region->coordinates,
                'is_default' => $region->is_default,
                'is_active' => $region->is_active,
                'stats' => $stats,
                'nodes' => $nodes,
            ],
        ]);
    }

    /**
     * Get nodes for a region
     */
    public function nodes(Request $request, string $code): JsonResponse
    {
        $region = $this->regionService->getRegionByCode($code);

        if (!$region) {
            return response()->json([
                'error' => 'Region not found',
            ], 404);
        }

        $nodes = $this->regionService->getRegionNodes($region->id);

        return response()->json([
            'object' => 'list',
            'data' => $nodes,
        ]);
    }

    /**
     * Get region statistics
     */
    public function stats(Request $request, string $code): JsonResponse
    {
        $region = $this->regionService->getRegionByCode($code);

        if (!$region) {
            return response()->json([
                'error' => 'Region not found',
            ], 404);
        }

        $stats = $this->regionService->getRegionStats($region->id);

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Recommend best region for deployment
     */
    public function recommend(Request $request): JsonResponse
    {
        $preferredRegion = $request->input('preferred');
        
        $region = $this->regionService->selectBestRegion($preferredRegion);

        if (!$region) {
            return response()->json([
                'error' => 'No suitable region available',
            ], 503);
        }

        return response()->json([
            'data' => [
                'id' => $region->uuid,
                'name' => $region->name,
                'code' => $region->code,
                'display_name' => $region->display_name,
                'is_default' => $region->is_default,
                'reason' => $region->is_default && !$preferredRegion 
                    ? 'Default region with available capacity' 
                    : 'Selected based on available capacity',
            ],
        ]);
    }
}