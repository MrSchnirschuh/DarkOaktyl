<?php

namespace DarkOak\Transformers\Api\Client;

use DarkOak\Models\ApiKey;
use DarkOak\Transformers\Api\Transformer;

class ApiKeyTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return ApiKey::RESOURCE_NAME;
    }

    /**
     * Transform this model into a representation that can be consumed by a client.
     */
    public function transform(ApiKey $model): array
    {
        return [
            'identifier' => $model->identifier,
            'description' => $model->memo,
            'allowed_ips' => $model->allowed_ips,
            'scopes' => $model->scopes ?? [],
            'last_used_at' => $model->last_used_at ? $model->last_used_at : null,
            'created_at' => $model->created_at->toIso8601String(),
        ];
    }
}

