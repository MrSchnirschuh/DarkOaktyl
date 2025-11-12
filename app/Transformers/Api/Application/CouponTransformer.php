<?php

namespace DarkOak\Transformers\Api\Application;

use DarkOak\Models\Billing\Coupon;
use DarkOak\Transformers\Api\Transformer;

class CouponTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return Coupon::RESOURCE_NAME;
    }

    public function transform(Coupon $model): array
    {
    $model->loadMissing(['term', 'redemptions', 'parent', 'personalizedFor']);

        return [
            'id' => $model->id,
            'uuid' => $model->uuid,
            'code' => $model->code,
            'name' => $model->name,
            'description' => $model->description,
            'type' => $model->type,
            'value' => $model->value,
            'percentage' => $model->percentage,
            'max_usages' => $model->max_usages,
            'per_user_limit' => $model->per_user_limit,
            'applies_to_term_id' => $model->applies_to_term_id,
            'parent_coupon_id' => $model->parent_coupon_id,
            'personalized_for_id' => $model->personalized_for_id,
            'parent_coupon_uuid' => $model->parent ? $model->parent->uuid : null,
            'personalized_for' => $model->personalizedFor ? $model->personalizedFor->only(['id', 'uuid', 'email']) : null,
            'term' => $model->term ? [
                'id' => $model->term->id,
                'uuid' => $model->term->uuid,
                'name' => $model->term->name,
            ] : null,
            'usage_count' => $model->redemptions->count(),
            'is_active' => $model->is_active,
            'starts_at' => self::formatTimestamp($model->starts_at),
            'expires_at' => self::formatTimestamp($model->expires_at),
            'metadata' => $model->metadata,
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
    }
}

