<?php

namespace Everest\Models\Billing;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Everest\Models\Model;

/**
 * @property int $id
 * @property int $resource_price_id
 * @property int $threshold
 * @property float $multiplier
 * @property string $mode
 * @property string|null $label
 * @property array|null $metadata
 */
class ResourceScalingRule extends Model
{
    public const RESOURCE_NAME = 'billing_resource_scaling_rule';

    protected $table = 'billing_resource_scaling_rules';

    protected $fillable = [
        'resource_price_id',
        'threshold',
        'multiplier',
        'mode',
        'label',
        'metadata',
    ];

    protected $casts = [
        'resource_price_id' => 'integer',
        'threshold' => 'integer',
        'multiplier' => 'float',
        'metadata' => 'array',
    ];

    protected $touches = ['resource'];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(ResourcePrice::class, 'resource_price_id');
    }
}
