<?php

namespace DarkOak\Transformers\Api\Application;

use DarkOak\Models\Billing\BillingTerm;
use DarkOak\Transformers\Api\Transformer;

class BillingTermTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return BillingTerm::RESOURCE_NAME;
    }

    public function transform(BillingTerm $model): array
    {
        return [
            'id' => $model->id,
            'uuid' => $model->uuid,
            'name' => $model->name,
            'slug' => $model->slug,
            'duration_days' => $model->duration_days,
            'multiplier' => $model->multiplier,
            'is_active' => $model->is_active,
            'is_default' => $model->is_default,
            'sort_order' => $model->sort_order,
            'metadata' => $model->metadata,
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
    }
}

