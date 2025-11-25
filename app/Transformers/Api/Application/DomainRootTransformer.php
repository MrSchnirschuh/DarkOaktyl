<?php

namespace DarkOak\Transformers\Api\Application;

use DarkOak\Models\DomainRoot;
use DarkOak\Transformers\Api\Transformer;

class DomainRootTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return DomainRoot::RESOURCE_NAME;
    }

    public function transform(DomainRoot $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'root_domain' => $model->root_domain,
            'provider' => $model->provider,
            'provider_config' => $model->provider_config ?? [],
            'is_active' => $model->is_active,
            'created_at' => $model->created_at?->toAtomString(),
            'updated_at' => $model->updated_at?->toAtomString(),
        ];
    }
}
