<?php

namespace DarkOak\Transformers\Api\Client;

use DarkOak\Models\Region;
use League\Fractal\Resource\Collection;
use DarkOak\Transformers\Api\Transformer;

class RegionTransformer extends Transformer
{
    protected array $defaultIncludes = ['nodes'];

    protected array $availableIncludes = [];

    public function getResourceName(): string
    {
        return Region::RESOURCE_NAME;
    }

    /**
     * Transform a region model into a representation that can be returned
     * to a client.
     */
    public function transform(Region $region): array
    {
        return [
            'id' => $region->id,
            'uuid' => $region->uuid,
            'name' => $region->name,
            'code' => $region->code,
            'display_name' => $region->display_name,
            'description' => $region->description,
            'timezone' => $region->timezone,
            'coordinates' => $region->coordinates,
            'is_active' => $region->is_active,
            'is_default' => $region->is_default,
            'ping_endpoint' => $region->ping_endpoint,
            'created_at' => self::formatTimestamp($region->created_at),
            'updated_at' => self::formatTimestamp($region->updated_at),
        ];
    }

    /**
     * Returns the nodes associated with this region.
     */
    public function includeNodes(Region $region): Collection
    {
        return $this->collection(
            $region->nodes->where('public', true)->where('maintenance_mode', false),
            new NodeTransformer()
        );
    }
}
