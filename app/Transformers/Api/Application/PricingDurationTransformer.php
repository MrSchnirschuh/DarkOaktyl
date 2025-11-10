<?php

namespace Everest\Transformers\Api\Application;

use Everest\Models\Billing\PricingDuration;
use Everest\Transformers\Api\Transformer;

class PricingDurationTransformer extends Transformer
{
    /**
     * Return the resource name for the transformer.
     */
    public function getResourceName(): string
    {
        return PricingDuration::RESOURCE_NAME;
    }

    /**
     * Transform a pricing duration into a representation suitable for the API.
     */
    public function transform(PricingDuration $model): array
    {
        return [
            'id' => $model->id,
            'pricing_configuration_id' => $model->pricing_configuration_id,
            'duration_days' => $model->duration_days,
            'price_factor' => $model->price_factor,
            'enabled' => $model->enabled,
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
    }
}
