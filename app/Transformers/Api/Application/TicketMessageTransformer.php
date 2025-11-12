<?php

namespace DarkOak\Transformers\Api\Application;

use DarkOak\Models\TicketMessage;
use DarkOak\Transformers\Api\Transformer;

class TicketMessageTransformer extends Transformer
{
    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return TicketMessage::RESOURCE_NAME;
    }

    /**
     * Transform this model into a representation that can be consumed by a client.
     */
    public function transform(TicketMessage $model): array
    {
        return [
            'id' => $model->id,
            'message' => $model->message,
            'author' => $model->user,
            'created_at' => $model->created_at->toAtomString(),
            'updated_at' => $model->updated_at ? $model->updated_at->toAtomString() : null,
        ];
    }
}

