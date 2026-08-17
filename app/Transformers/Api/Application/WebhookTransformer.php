<?php

namespace DarkOak\Transformers\Api\Application;

use DarkOak\Models\Webhook;
use DarkOak\Transformers\Api\Transformer;

class WebhookTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return 'webhook';
    }

    public function transform(Webhook $model): array
    {
        return [
            'id' => $model->id,
            'uuid' => $model->uuid,
            'name' => $model->name,
            'url' => $model->url,
            'events' => $model->events ?? [],
            'enabled' => (bool) $model->enabled,
            'has_secret' => !empty($model->secret),
            'last_response_code' => $model->last_response_code,
            'last_sent_at' => $model->last_sent_at?->toIso8601String(),
            'stats' => [
                'successful_count' => (int) ($model->successful_count ?? 0),
                'failed_count' => (int) ($model->failed_count ?? 0),
            ],
            'created_at' => $model->created_at?->toIso8601String(),
            'updated_at' => $model->updated_at?->toIso8601String(),
        ];
    }
}
