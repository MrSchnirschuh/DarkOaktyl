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

    /**
     * Transform this model into a representation that can be consumed by a client.
     */
    public function transform(Webhook $model): array
    {
        return [
            'id' => $model->id,
            'uuid' => $model->uuid,
            'name' => $model->name,
            'url' => $model->url,
            'secret' => $model->secret ? '••••••••' : null,
            'has_secret' => !empty($model->secret),
            'events' => $model->events,
            'enabled' => $model->enabled,
            'last_response_code' => $model->last_response_code,
            'last_sent_at' => $this->formatTimestamp($model->last_sent_at),
            'stats' => [
                'successful_count' => $model->successful_count ?? 0,
                'failed_count' => $model->failed_count ?? 0,
            ],
            'created_at' => $this->formatTimestamp($model->created_at),
            'updated_at' => $this->formatTimestamp($model->updated_at),
        ];
    }
}