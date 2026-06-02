<?php

namespace DarkOak\Transformers\Api\Client;

use DarkOak\Models\UserPasskey;
use DarkOak\Transformers\Api\Transformer;

class PasskeyTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return UserPasskey::RESOURCE_NAME;
    }

    /**
     * Returns a WebAuthn passkey in an API response format.
     */
    public function transform(UserPasskey $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'credential_id' => $model->credential_id,
            'type' => $model->type,
            'last_used_at' => $model->last_used_at?->toIso8601String(),
            'created_at' => $model->created_at->toIso8601String(),
        ];
    }
}