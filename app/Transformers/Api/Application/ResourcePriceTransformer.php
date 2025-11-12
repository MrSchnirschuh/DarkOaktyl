<?php

namespace DarkOak\Transformers\Api\Application;

use DarkOak\Models\Billing\ResourcePrice;
use DarkOak\Models\Billing\ResourceScalingRule;
use DarkOak\Transformers\Api\Transformer;

class ResourcePriceTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return ResourcePrice::RESOURCE_NAME;
    }

    public function transform(ResourcePrice $model): array
    {
        $model->loadMissing('scalingRules');

        return [
            'id' => $model->id,
            'uuid' => $model->uuid,
            'resource' => $model->resource,
            'display_name' => $model->display_name,
            'description' => $model->description,
            'unit' => $model->unit,
            'base_quantity' => $model->base_quantity,
            'price' => $model->price,
            'currency' => $model->currency,
            'min_quantity' => $model->min_quantity,
            'max_quantity' => $model->max_quantity,
            'default_quantity' => $model->default_quantity,
            'step_quantity' => $model->step_quantity,
            'is_visible' => $model->is_visible,
            'is_metered' => $model->is_metered,
            'sort_order' => $model->sort_order,
            'metadata' => $model->metadata,
            'scaling_rules' => $model->scalingRules->map(function (ResourceScalingRule $rule) {
                return [
                    'id' => $rule->id,
                    'threshold' => $rule->threshold,
                    'multiplier' => $rule->multiplier,
                    'mode' => $rule->mode,
                    'label' => $rule->label,
                    'metadata' => $rule->metadata,
                    'created_at' => self::formatTimestamp($rule->created_at),
                    'updated_at' => self::formatTimestamp($rule->updated_at),
                ];
            })->values()->toArray(),
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
    }
}

