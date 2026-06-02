<?php

namespace DarkOak\Transformers\Api\Application\Domains;

use DarkOak\Models\DomainRoot;
use DarkOak\Transformers\Api\Application\ApplicationApiTransformer;

class DomainRootTransformer extends ApplicationApiTransformer
{
    public function transform(DomainRoot $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'root_domain' => $model->root_domain,
            'provider' => $model->provider,
            'provider_config' => $model->provider_config ?? [],
            'is_active' => $model->is_active,
            'created_at' => $model->created_at?->toISOString(),
            'updated_at' => $model->updated_at?->toISOString(),
        ];
    }
}