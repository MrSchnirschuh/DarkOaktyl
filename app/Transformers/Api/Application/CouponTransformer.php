<?php

namespace Everest\Transformers\Api\Application;

use Everest\Models\Billing\Coupon;
use Everest\Transformers\Api\Transformer;

class CouponTransformer extends Transformer
{
    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return Coupon::RESOURCE_NAME;
    }

    /**
     * Transform this model into a representation that can be consumed by a client.
     */
    public function transform(Coupon $model): array
    {
        return [
            'id' => $model->id,
            'code' => $model->code,
            'description' => $model->description,
            'discount_type' => $model->discount_type,
            'discount_value' => $model->discount_value,
            'max_uses' => $model->max_uses,
            'uses' => $model->uses,
            'expires_at' => $model->expires_at ? $model->expires_at->toAtomString() : null,
            'is_active' => $model->is_active,
            'is_valid' => $model->isValid(),
            'created_at' => $model->created_at->toAtomString(),
            'updated_at' => $model->updated_at ? $model->updated_at->toAtomString() : null,
        ];
    }
}
