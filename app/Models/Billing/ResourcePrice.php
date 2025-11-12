<?php

namespace Everest\Models\Billing;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Everest\Models\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $resource
 * @property string $display_name
 * @property string|null $description
 * @property string|null $unit
 * @property int $base_quantity
 * @property float $price
 * @property string $currency
 * @property int $min_quantity
 * @property int|null $max_quantity
 * @property int $default_quantity
 * @property int $step_quantity
 * @property bool $is_visible
 * @property bool $is_metered
 * @property int $sort_order
 * @property array|null $metadata
 */
class ResourcePrice extends Model
{
    public const RESOURCE_NAME = 'billing_resource_price';

    protected $table = 'billing_resource_prices';

    protected $fillable = [
        'uuid',
        'resource',
        'display_name',
        'description',
        'unit',
        'base_quantity',
        'price',
        'currency',
        'min_quantity',
        'max_quantity',
        'default_quantity',
        'step_quantity',
        'is_visible',
        'is_metered',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'base_quantity' => 'integer',
        'price' => 'float',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'default_quantity' => 'integer',
        'step_quantity' => 'integer',
        'is_visible' => 'bool',
        'is_metered' => 'bool',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::addGlobalScope('ordered', function ($query) {
            $query->orderBy('sort_order')->orderBy('id');
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function scalingRules(): HasMany
    {
        return $this->hasMany(ResourceScalingRule::class, 'resource_price_id')->orderBy('threshold');
    }
}
