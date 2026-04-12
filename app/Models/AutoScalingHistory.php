<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * \DarkOak\Models\AutoScalingHistory.
 *
 * @property int $id
 * @property int $server_id
 * @property int|null $auto_scaling_rule_id
 * @property string $action
 * @property int $old_memory
 * @property int $new_memory
 * @property float|null $cpu_percent
 * @property float|null $memory_percent
 * @property float|null $disk_percent
 * @property string|null $reason
 * @property string|null $triggered_by
 * @property string $status
 * @property string|null $error_message
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \DarkOak\Models\Server $server
 * @property \DarkOak\Models\AutoScalingRule|null $autoScalingRule
 */
class AutoScalingHistory extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'auto_scaling_history';

    /**
     * Actions.
     */
    public const ACTION_SCALE_UP = 'scale_up';
    public const ACTION_SCALE_DOWN = 'scale_down';
    public const ACTION_SKIPPED = 'skipped';

    /**
     * Status values.
     */
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PENDING = 'pending';

    /**
     * The table associated with the model.
     */
    protected $table = 'auto_scaling_histories';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'server_id',
        'auto_scaling_rule_id',
        'action',
        'old_memory',
        'new_memory',
        'cpu_percent',
        'memory_percent',
        'disk_percent',
        'reason',
        'triggered_by',
        'status',
        'error_message',
        'metadata',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'server_id' => 'integer',
        'auto_scaling_rule_id' => 'integer',
        'old_memory' => 'integer',
        'new_memory' => 'integer',
        'cpu_percent' => 'float',
        'memory_percent' => 'float',
        'disk_percent' => 'float',
        'metadata' => 'array',
    ];

    /**
     * Gets the server associated with this history entry.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Gets the auto-scaling rule associated with this history entry.
     */
    public function autoScalingRule(): BelongsTo
    {
        return $this->belongsTo(AutoScalingRule::class, 'auto_scaling_rule_id');
    }

    /**
     * Check if this history entry represents a scale up action.
     */
    public function isScaleUp(): bool
    {
        return $this->action === self::ACTION_SCALE_UP;
    }

    /**
     * Check if this history entry represents a scale down action.
     */
    public function isScaleDown(): bool
    {
        return $this->action === self::ACTION_SCALE_DOWN;
    }

    /**
     * Check if this action was skipped.
     */
    public function isSkipped(): bool
    {
        return $this->action === self::ACTION_SKIPPED;
    }

    /**
     * Check if this action completed successfully.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if this action failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Get the memory change in MB.
     */
    public function getMemoryChange(): int
    {
        return $this->new_memory - $this->old_memory;
    }

    /**
     * Create a new history entry for a scaling action.
     */
    public static function log(
        Server $server,
        ?AutoScalingRule $rule,
        string $action,
        int $oldMemory,
        int $newMemory,
        array $metrics = [],
        ?string $reason = null,
        ?string $triggeredBy = null,
        string $status = self::STATUS_COMPLETED,
        ?string $errorMessage = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'server_id' => $server->id,
            'auto_scaling_rule_id' => $rule?->id,
            'action' => $action,
            'old_memory' => $oldMemory,
            'new_memory' => $newMemory,
            'cpu_percent' => $metrics['cpu'] ?? null,
            'memory_percent' => $metrics['memory'] ?? null,
            'disk_percent' => $metrics['disk'] ?? null,
            'reason' => $reason,
            'triggered_by' => $triggeredBy,
            'status' => $status,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Scope query to only completed entries.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope query to only failed entries.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope query to only scale up entries.
     */
    public function scopeScaleUp($query)
    {
        return $query->where('action', self::ACTION_SCALE_UP);
    }

    /**
     * Scope query to only scale down entries.
     */
    public function scopeScaleDown($query)
    {
        return $query->where('action', self::ACTION_SCALE_DOWN);
    }
}
