<?php

namespace Everest\Models\Billing;

use Everest\Models\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $slug
 * @property int $duration_days
 * @property float $multiplier
 * @property bool $is_active
 * @property bool $is_default
 * @property int $sort_order
 * @property array|null $metadata
 */
class BillingTerm extends Model
{
    public const RESOURCE_NAME = 'billing_term';

    protected $table = 'billing_terms';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'duration_days',
        'multiplier',
        'is_active',
        'is_default',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'multiplier' => 'float',
        'is_active' => 'bool',
        'is_default' => 'bool',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
