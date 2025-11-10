<?php

namespace Everest\Models\Billing;

use Everest\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pricing_configuration_id
 * @property int $duration_days
 * @property float $price_factor
 * @property bool $enabled
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PricingDuration extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'pricing_duration';

    /**
     * The table associated with the model.
     */
    protected $table = 'pricing_durations';

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'pricing_configuration_id',
        'duration_days',
        'price_factor',
        'enabled',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'pricing_configuration_id' => 'integer',
        'duration_days' => 'integer',
        'price_factor' => 'float',
        'enabled' => 'boolean',
    ];

    public static array $validationRules = [
        'pricing_configuration_id' => 'required|exists:pricing_configurations,id',
        'duration_days' => 'required|integer|min:1',
        'price_factor' => 'required|numeric|min:0',
        'enabled' => 'boolean',
    ];

    /**
     * Get the pricing configuration this duration belongs to.
     */
    public function pricingConfiguration(): BelongsTo
    {
        return $this->belongsTo(PricingConfiguration::class, 'pricing_configuration_id');
    }
}
