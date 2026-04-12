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

    /**
     * Transform this model into a representation that can be consumed by a client.
     */
    public function transform(WebhookLog $model): array
    {
        return [
            'id' => $model->id,
            'webhook_id' => $model->webhook_id,
            'event' => $model->event,
            'payload' => $model->getDecodedPayload(),
            'attempt' => $model->attempt,
            'response_code' => $model->response_code,
            'response_body' => $model->response_body ? json_decode($model->response_body) : null,
            'success' => $model->success,
            'error_message' => $model->error_message,
            'created_at' => $this->formatTimestamp($model->created_at),
            'updated_at' => $this->formatTimestamp($model->updated_at),
        ];
    }
}