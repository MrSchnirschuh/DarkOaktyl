<?php

namespace DarkOak\Transformers\Api\Client;

use DarkOak\Models\Organization;
use DarkOak\Transformers\Api\Transformer;

class OrganizationTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return Organization::RESOURCE_NAME;
    }

    public function transform(Organization $organization): array
    {
        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'description' => $organization->description,
            'slug' => $organization->slug,
            'avatar' => $organization->avatar,
            'owner_id' => $organization->owner_id,
            'members_count' => $organization->members_count ?? $organization->members()->count(),
            'servers_count' => $organization->servers_count ?? $organization->servers()->count(),
            'created_at' => $organization->created_at?->toIso8601String(),
            'updated_at' => $organization->updated_at?->toIso8601String(),
        ];
    }
}
