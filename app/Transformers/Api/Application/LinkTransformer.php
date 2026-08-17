<?php

namespace DarkOak\Transformers\Api\Application;

use DarkOak\Models\CustomLink;
use DarkOak\Transformers\Api\Transformer;

class LinkTransformer extends Transformer
{
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
            'icon' => $model->icon,
            'visible' => $model->visible,
            'created_at' => $model->created_at->toIso8601String(),
            'updated_at' => $model->updated_at?->toIso8601String(),
        ];
    }
}
