<?php

namespace DarkOak\Models\Billing;

use Carbon\Carbon;
use DarkOak\Models\User;
use DarkOak\Models\Model;
use DarkOak\Models\Server;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $server_id
 * @property Carbon $billing_period_start
 * @property Carbon $billing_period_end
 * @property float $hours_billed
 * @property float $hourly_rate
 * @property float $amount
 * @property string $status
 * @property Carbon|null $processed_at
 * @property string|null $error_message
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read Server $server
 */
class BillingRecord extends Model
{
    public const RESOURCE_NAME = 'billing_record';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    protected $table = 'billing_records';

    protected $fillable = [
        'user_id',
        'server_id',
        'billing_period_start',
        'billing_period_end',
        'hours_billed',
        'hourly_rate',
        'amount',
        'status',
        'processed_at',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'server_id' => 'integer',
        'billing_period_start' => 'datetime',
        'billing_period_end' => 'datetime',
        'hours_billed' => 'float',
        'hourly_rate' => 'float',
        'amount' => 'float',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public static array $validationRules = [
        'user_id' => 'required|integer|exists:users,id',
        'server_id' => 'required|integer|exists:servers,id',
        'billing_period_start' => 'required|date',
        'billing_period_end' => 'required|date|after_or_equal:billing_period_start',
        'hours_billed' => 'required|numeric|min:0',
        'hourly_rate' => 'required|numeric|min:0',
        'amount' => 'required|numeric|min:0',
        'status' => 'required|in:pending,processed,failed,refunded',
        'processed_at' => 'nullable|date',
        'error_message' => 'nullable|string',
        'metadata' => 'nullable|array',
    ];

    /**
     * Get the user that owns this billing record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the server associated with this billing record.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Check if the record is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the record is processed.
     */
    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    /**
     * Mark the record as processed.
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSED,
            'processed_at' => Carbon::now(),
        ]);
    }

    /**
     * Mark the record as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark the record as refunded.
     */
    public function markAsRefunded(): void
    {
        $this->update([
            'status' => self::STATUS_REFUNDED,
        ]);
    }

    /**
     * Calculate cost for a given number of hours.
     */
    public static function calculateCost(float $hours, float $hourlyRate): float
    {
        return round($hours * $hourlyRate, 4);
    }
}
