<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * \DarkOak\Models\AutoScalingRule.
 *
 * @property int $id
 * @property int $server_id
 * @property int $cpu_threshold
 * @property int $memory_threshold
 * @property int $disk_threshold
 * @property int $scale_up_step
 * @property int $scale_down_step
 * @property int $min_memory
 * @property int $max_memory
 * @property int $scale_up_cooldown
 * @property int $scale_down_cooldown
 * @property bool $enabled
 * @property \Illuminate\Support\Carbon|null $last_scale_up_at
 * @property \Illuminate\Support\Carbon|null $last_scale_down_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \DarkOak\Models\Server $server
 * @property \Illuminate\Database\Eloquent\Collection|\DarkOak\Models\AutoScalingHistory[] $histories
 */
class AutoScalingRule extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'auto_scaling_rule';

    /**
     * The table associated with the model.
     */
    protected $table = 'auto_scaling_rules';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'server_id',
        'cpu_threshold',
        'memory_threshold',
        'disk_threshold',
        'scale_up_step',
        'scale_down_step',
        'min_memory',
        'max_memory',
        'scale_up_cooldown',
        'scale_down_cooldown',
        'enabled',
        'last_scale_up_at',
        'last_scale_down_at',
    ];

    /**
     * Validation rules for creating/updating auto-scaling rules.
     */
    public static array $validationRules = [
        'server_id' => 'required|exists:servers,id|unique:auto_scaling_rules',
        'cpu_threshold' => 'required|integer|between:1,100',
        'memory_threshold' => 'required|integer|between:1,100',
        'disk_threshold' => 'required|integer|between:1,100',
        'scale_up_step' => 'required|integer|min:64',
        'scale_down_step' => 'required|integer|min:64',
        'min_memory' => 'required|integer|min:128',
        'max_memory' => 'required|integer|min:256',
        'scale_up_cooldown' => 'required|integer|min:1',
        'scale_down_cooldown' => 'required|integer|min:1',
        'enabled' => 'boolean',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'server_id' => 'integer',
        'cpu_threshold' => 'integer',
        'memory_threshold' => 'integer',
        'disk_threshold' => 'integer',
        'scale_up_step' => 'integer',
        'scale_down_step' => 'integer',
        'min_memory' => 'integer',
        'max_memory' => 'integer',
        'scale_up_cooldown' => 'integer',
        'scale_down_cooldown' => 'integer',
        'enabled' => 'boolean',
        'last_scale_up_at' => 'datetime',
        'last_scale_down_at' => 'datetime',
    ];

    /**
     * Gets the server associated with this auto-scaling rule.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Gets the history entries for this rule.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(AutoScalingHistory::class, 'auto_scaling_rule_id');
    }

    /**
     * Check if scaling up is allowed based on cooldown.
     */
    public function canScaleUp(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if ($this->last_scale_up_at === null) {
            return true;
        }

        return $this->last_scale_up_at->diffInMinutes(now()) >= $this->scale_up_cooldown;
    }

    /**
     * Check if scaling down is allowed based on cooldown.
     */
    public function canScaleDown(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if ($this->last_scale_down_at === null) {
            return true;
        }

        return $this->last_scale_down_at->diffInMinutes(now()) >= $this->scale_down_cooldown;
    }

    /**
     * Check if the current memory allocation is at the maximum limit.
     */
    public function isAtMaxMemory(int $currentMemory): bool
    {
        return $currentMemory >= $this->max_memory;
    }

    /**
     * Check if the current memory allocation is at the minimum limit.
     */
    public function isAtMinMemory(int $currentMemory): bool
    {
        return $currentMemory <= $this->min_memory;
    }

    /**
     * Calculate the new memory value when scaling up.
     */
    public function calculateScaleUp(int $currentMemory): int
    {
        $newMemory = $currentMemory + $this->scale_up_step;
        return min($newMemory, $this->max_memory);
    }

    /**
     * Calculate the new memory value when scaling down.
     */
    public function calculateScaleDown(int $currentMemory): int
    {
        $newMemory = $currentMemory - $this->scale_down_step;
        return max($newMemory, $this->min_memory);
    }
}
