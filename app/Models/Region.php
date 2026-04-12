<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $code
 * @property string $display_name
 * @property string|null $description
 * @property string $timezone
 * @property array|null $coordinates
 * @property bool $is_active
 * @property bool $is_default
 * @property string|null $ping_endpoint
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property Node[]|\Illuminate\Database\Eloquent\Collection $nodes
 */
class Region extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'region';

    /**
     * The table associated with the model.
     */
    protected $table = 'regions';

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'coordinates' => 'array',
    ];

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'name',
        'code',
        'display_name',
        'description',
        'timezone',
        'coordinates',
        'is_active',
        'is_default',
        'ping_endpoint',
    ];

    public static array $validationRules = [
        'name' => 'required|string|max:100',
        'code' => 'required|string|max:10|unique:regions',
        'display_name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'timezone' => 'required|string|max:50',
        'coordinates' => 'nullable|array',
        'coordinates.lat' => 'nullable|numeric|between:-90,90',
        'coordinates.lng' => 'nullable|numeric|between:-180,180',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'ping_endpoint' => 'nullable|string|url|max:255',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate UUID if not provided
        static::creating(function (Region $region) {
            if (empty($region->uuid)) {
                $region->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });

        // Ensure only one default region
        static::saving(function (Region $region) {
            if ($region->is_default) {
                static::where('id', '!=', $region->id ?? 0)->update(['is_default' => false]);
            }
        });
    }

    /**
     * Gets the nodes associated with this region.
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    /**
     * Get the default region.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Get active regions only.
     */
    public static function active()
    {
        return static::where('is_active', true);
    }

    /**
     * Calculate approximate latency to this region.
     * This is a placeholder that can be implemented with actual ping logic.
     */
    public function getLatency(): ?int
    {
        if (!$this->ping_endpoint) {
            return null;
        }

        // Placeholder: In production, implement actual latency check
        // Could use JS ping from client or backend curl
        return null;
    }
}
