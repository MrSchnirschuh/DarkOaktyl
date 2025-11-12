<?php

namespace DarkOak\Transformers\Api\Application;

use DarkOak\Models\CustomLink;
use DarkOak\Transformers\Api\Transformer;

class LinkTransformer extends Transformer
{
    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return CustomLink::RESOURCE_NAME;
    }

    /**
     * Transform this model into a representation that can be consumed by a client.
     */
    public function transform(CustomLink $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'url' => $model->url,
            'visible' => $model->visible,
            'created_at' => $model->created_at->toAtomString(),
            'updated_at' => $model->updated_at ? $model->updated_at->toAtomString() : null,
        ];
    }
}

