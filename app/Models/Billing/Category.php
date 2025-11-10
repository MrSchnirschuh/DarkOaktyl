<?php

namespace Everest\Models\Billing;

use Everest\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $icon
 * @property string $description
 * @property bool $visible
 * @property int $nest_id
 * @property int $egg_id
 * @property int|null $pricing_configuration_id
 * @property bool $use_configurator
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Category extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'category';

    /**
     * The table associated with the model.
     */
    protected $table = 'categories';

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'uuid', 'name', 'visible', 'icon',
        'description', 'nest_id', 'egg_id',
        'pricing_configuration_id', 'use_configurator',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'visible' => 'boolean',
        'use_configurator' => 'boolean',
        'pricing_configuration_id' => 'integer',
    ];

    public static array $validationRules = [
        'uuid' => 'required|string|size:36',
        'name' => 'required|string|min:3|max:191',
        'icon' => 'nullable|string|max:300',
        'description' => 'nullable|string|max:300',
        'visible' => 'nullable|bool',
        'nest_id' => 'required|exists:nests,id',
        'egg_id' => 'required|exists:eggs,id',
        'pricing_configuration_id' => 'nullable|exists:pricing_configurations,id',
        'use_configurator' => 'nullable|bool',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_uuid');
    }

    public function pricingConfiguration(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PricingConfiguration::class, 'pricing_configuration_id');
    }
}
