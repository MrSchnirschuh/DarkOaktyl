<?php

namespace DarkOak\Transformers\Api\Application;

use DarkOak\Models\Billing\Order;
use DarkOak\Transformers\Api\Transformer;

class OrderTransformer extends Transformer
{
    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return Order::RESOURCE_NAME;
    }

    /**
     * Transform this model into a representation that can be consumed by a client.
     */
    public function transform(Order $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'user_id' => $model->user_id,
            'description' => $model->description,
            'total' => $model->total,
            'status' => $model->status,
            'product_id' => $model->product_id,
            'type' => $model->type ?? '?',
            'payment_intent_id' => $model->payment_intent_id,
            'threat_index' => $model->threat_index,
            'created_at' => $model->created_at->toAtomString(),
            'updated_at' => $model->updated_at ? $model->updated_at->toAtomString() : null,
        ];
    }
}

