<?php

namespace DarkOak\Transformers\Api\Application;

use DarkOak\Models\WebhookEvent;
use DarkOak\Transformers\Api\Transformer;

class WebhookEventTransformer extends Transformer
{
    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return 'webhook_event';
    }

    /**
     * Transform this model into a representation that can be consumed by a client.
     */
    public function transform(WebhookEvent $model): array
    {
        return [
            'id' => $model->id,
            'key' => $model->key,
            'description' => $model->description,
            'enabled' => $model->enabled,
            'created_at' => $model->created_at->toAtomString(),
            'updated_at' => $model->updated_at ? $model->updated_at->toAtomString() : null,
        ];
    }
}

