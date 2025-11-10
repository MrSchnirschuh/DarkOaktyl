<?php

namespace Everest\Models\Billing;

use Everest\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property bool $enabled
 * @property float $cpu_price
 * @property float $memory_price
 * @property float $disk_price
 * @property float $backup_price
 * @property float $database_price
 * @property float $allocation_price
 * @property float $small_package_factor
 * @property float $medium_package_factor
 * @property float $large_package_factor
 * @property int $small_package_threshold
 * @property int $large_package_threshold
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PricingConfiguration extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'pricing_configuration';

    /**
     * The table associated with the model.
     */
    protected $table = 'pricing_configurations';

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'name',
        'enabled',
        'cpu_price',
        'memory_price',
        'disk_price',
        'backup_price',
        'database_price',
        'allocation_price',
        'small_package_factor',
        'medium_package_factor',
        'large_package_factor',
        'small_package_threshold',
        'large_package_threshold',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'enabled' => 'boolean',
        'cpu_price' => 'float',
        'memory_price' => 'float',
        'disk_price' => 'float',
        'backup_price' => 'float',
        'database_price' => 'float',
        'allocation_price' => 'float',
        'small_package_factor' => 'float',
        'medium_package_factor' => 'float',
        'large_package_factor' => 'float',
        'small_package_threshold' => 'integer',
        'large_package_threshold' => 'integer',
    ];

    public static array $validationRules = [
        'uuid' => 'required|string|size:36',
        'name' => 'required|string|min:3|max:191',
        'enabled' => 'boolean',
        'cpu_price' => 'required|numeric|min:0',
        'memory_price' => 'required|numeric|min:0',
        'disk_price' => 'required|numeric|min:0',
        'backup_price' => 'required|numeric|min:0',
        'database_price' => 'required|numeric|min:0',
        'allocation_price' => 'required|numeric|min:0',
        'small_package_factor' => 'required|numeric|min:0',
        'medium_package_factor' => 'required|numeric|min:0',
        'large_package_factor' => 'required|numeric|min:0',
        'small_package_threshold' => 'required|integer|min:0',
        'large_package_threshold' => 'required|integer|min:0',
    ];

    /**
     * Get duration pricing options for this configuration.
     */
    public function durations(): HasMany
    {
        return $this->hasMany(PricingDuration::class, 'pricing_configuration_id');
    }

    /**
     * Calculate the price for a given set of resources.
     */
    public function calculatePrice(
        int $cpu,
        int $memory,
        int $disk,
        int $backups,
        int $databases,
        int $allocations
    ): float {
        $basePrice = 
            ($cpu * $this->cpu_price) +
            ($memory * $this->memory_price) +
            ($disk * $this->disk_price) +
            ($backups * $this->backup_price) +
            ($databases * $this->database_price) +
            ($allocations * $this->allocation_price);

        // Apply package size factor
        $factor = $this->getPackageFactor($memory);

        return round($basePrice * $factor, 2);
    }

    /**
     * Get the appropriate package size factor based on memory allocation.
     */
    private function getPackageFactor(int $memory): float
    {
        if ($memory < $this->small_package_threshold) {
            return $this->small_package_factor;
        }

        if ($memory > $this->large_package_threshold) {
            return $this->large_package_factor;
        }

        return $this->medium_package_factor;
    }
}
