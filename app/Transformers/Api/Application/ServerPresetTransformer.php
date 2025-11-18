<?php

namespace DarkOak\Transformers\Api\Application;

use DarkOak\Models\ServerPreset;
use DarkOak\Transformers\Api\Transformer;

class ServerPresetTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return 'server_preset';
    }

    public function transform(ServerPreset $model): array
    {
        return [
            'id' => $model->getKey(),
            'uuid' => $model->uuid,
            'name' => $model->name,
            'description' => $model->description,
            'settings' => $model->settings,
            'port_start' => $model->port_start,
            'port_end' => $model->port_end,
            'visibility' => $model->visibility,
            'user_id' => $model->user_id,
            'naming' => $model->naming,
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
    }
}
