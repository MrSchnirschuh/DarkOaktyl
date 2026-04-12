<?php

namespace DarkOak\Transformers\Api\Application;

use DarkOak\Models\WebhookLog;
use DarkOak\Transformers\Api\Transformer;

class WebhookLogTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return 'webhook_log';
    }

    public function transform(WebhookLog $model): array
    {
        return [
            'id' => $model->id,
            'webhook_id' => $model->webhook_id,
            'event' => $model->event,
            'payload' => $model->payload ?? [],
            'response_code' => $model->response_code,
            'response_body' => $model->response_body,
            'error_message' => $model->error_message,
            'attempt' => $model->attempt,
            'success' => (bool) $model->success,
            'created_at' => $model->created_at?->toIso8601String(),
            'updated_at' => $model->updated_at?->toIso8601String(),
        ];
    }
}

