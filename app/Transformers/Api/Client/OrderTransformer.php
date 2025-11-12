<?php

namespace DarkOak\Transformers\Api\Client;

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
            'description' => $model->description,
            'total' => $model->total,
            'status' => $model->status,
            'product_id' => $model->product_id,
            'type' => $model->type ?? '?',
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at,
        ];
    }
}

