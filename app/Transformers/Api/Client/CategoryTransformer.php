<?php

namespace DarkOak\Transformers\Api\Client;

use DarkOak\Models\Billing\Category;
use DarkOak\Transformers\Api\Transformer;

class CategoryTransformer extends Transformer
{
    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return Category::RESOURCE_NAME;
    }

    /**
     * Transform this model into a representation that can be consumed by a client.
     */
    public function transform(Category $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'icon' => $model->icon,
            'description' => $model->description,
        ];
    }
}

