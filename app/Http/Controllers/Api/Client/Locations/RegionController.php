<?php

namespace DarkOak\Http\Controllers\Api\Client\Locations;

use DarkOak\Models\Region;
use DarkOak\Transformers\Api\Client\RegionTransformer;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;

class RegionController extends ClientApiController
{
    /**
     * Get all active regions with their nodes and latency information.
     */
    public function index(): array
    {
        $regions = Region::active()
            ->with('nodes')
            ->orderBy('display_name')
            ->get();

        return $this->fractal->collection($regions)
            ->transformWith(RegionTransformer::class)
            ->toArray();
    }

    /**
     * Get a specific region by ID.
     */
    public function view(Region $region): array
    {
        return $this->fractal->item($region->load('nodes'))
            ->transformWith(RegionTransformer::class)
            ->toArray();
    }

    /**
     * Get latency information for all regions.
     * This is a lightweight endpoint that returns ping endpoints for client-side checks.
     */
    public function latency(): array
    {
        $regions = Region::active()
            ->select(['id', 'uuid', 'code', 'ping_endpoint'])
            ->whereNotNull('ping_endpoint')
            ->get();

        return [
            'data' => $regions->map(function (Region $region) {
                return [
                    'id' => $region->id,
                    'uuid' => $region->uuid,
                    'code' => $region->code,
                    'ping_endpoint' => $region->ping_endpoint,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get the default region.
     */
    public function default(): array
    {
        $region = Region::getDefault();

        if (!$region) {
            return [
                'data' => null,
            ];
        }

        return $this->fractal->item($region->load('nodes'))
            ->transformWith(RegionTransformer::class)
            ->toArray();
    }
}
