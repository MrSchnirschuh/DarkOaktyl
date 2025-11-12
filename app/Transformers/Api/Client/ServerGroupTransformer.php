<?php

namespace DarkOak\Transformers\Api\Client;

use DarkOak\Models\ServerGroup;
use DarkOak\Transformers\Api\Transformer;

class ServerGroupTransformer extends Transformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return 'server_group';
    }

    public function transform(ServerGroup $model): array
    {
        return [
            'id' => $model->id,
            'user_id' => $model->user_id,
            'name' => $model->name,
            'color' => $model->color,
        ];
    }
}

