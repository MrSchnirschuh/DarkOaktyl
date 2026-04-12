<?php

namespace DarkOak\Models\Billing;

use Carbon\Carbon;
use DarkOak\Models\Model;
use DarkOak\Models\Server;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $server_id
 * @property string $status
 * @property Carbon|null $started_at
 * @property Carbon|null $stopped_at
 * @property float $hours_accumulated
 * @property Carbon|null $last_billed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Server $server
 */
class ServerRuntimeTracking extends Model
{
    public const RESOURCE_NAME = 'server_runtime_tracking';

    public const STATUS_RUNNING = 'running';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_STOPPED = 'stopped';
    public const STATUS_OFFLINE = 'offline';

    protected $table = 'server_runtime_tracking';

    protected $fillable = [
        'server_id',
        'status',
        'started_at',
        'stopped_at',
        'hours_accumulated',
        'last_billed_at',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'hours_accumulated' => 'float',
        'last_billed_at' => 'datetime',
    ];

    public static array $validationRules = [
        'server_id' => 'required|integer|exists:servers,id',
        'status' => 'required|in:running,paused,stopped,offline',
        'started_at' => 'nullable|date',
        'stopped_at' => 'nullable|date',
        'hours_accumulated' => 'required|numeric|min:0',
        'last_billed_at' => 'nullable|date',
    ];

    /**
     * Get the server associated with this tracking record.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Start tracking runtime.
     */
    public function startTracking(): void
    {
        if ($this->status === self::STATUS_RUNNING) {
            return;
        }

        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => Carbon::now(),
            'stopped_at' => null,
        ]);
    }

    /**
     * Pause tracking (no billing during pause).
     */
    public function pauseTracking(): void
    {
        if ($this->status !== self::STATUS_RUNNING) {
            return;
        }

        $this->update([
            'status' => self::STATUS_PAUSED,
            'stopped_at' => Carbon::now(),
        ]);
    }

    /**
     * Stop tracking and accumulate hours.
     */
    public function stopTracking(): float
    {
        if ($this->status === self::STATUS_STOPPED || $this->status === self::STATUS_OFFLINE) {
            return 0;
        }

        $hoursSinceStart = 0;
        if ($this->started_at) {
            $endTime = Carbon::now();
            $hoursSinceStart = $this->started_at->diffInMinutes($endTime) / 60;
            $this->hours_accumulated += $hoursSinceStart;
        }

        $this->update([
            'status' => self::STATUS_STOPPED,
            'stopped_at' => Carbon::now(),
            'started_at' => null,
        ]);

        return $hoursSinceStart;
    }

    /**
     * Mark as offline (no billing).
     */
    public function markOffline(): void
    {
        if ($this->status === self::STATUS_RUNNING) {
            $this->stopTracking();
        }

        $this->update([
            'status' => self::STATUS_OFFLINE,
        ]);
    }

    /**
     * Get current runtime hours (accumulated + current session).
     */
    public function getCurrentRuntimeHours(): float
    {
        $hours = $this->hours_accumulated;

        if ($this->status === self::STATUS_RUNNING && $this->started_at) {
            $hours += $this->started_at->diffInMinutes(Carbon::now()) / 60;
        }

        return round($hours, 2);
    }

    /**
     * Reset accumulated hours after billing.
     */
    public function resetAfterBilling(): void
    {
        $this->update([
            'hours_accumulated' => 0,
            'last_billed_at' => Carbon::now(),
        ]);
    }

    /**
     * Check if server is currently running and billable.
     */
    public function isBillable(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if server is paused.
     */
    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    /**
     * Get or create tracking record for a server.
     */
    public static function forServer(int $serverId): self
    {
        return self::firstOrCreate(
            ['server_id' => $serverId],
            ['status' => self::STATUS_OFFLINE, 'hours_accumulated' => 0]
        );
    }

    /**
     * Get all servers that need billing (have accumulated hours or are running).
     */
    public static function getServersForBilling(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where(function ($query) {
            $query->where('hours_accumulated', '>', 0)
                ->orWhere('status', self::STATUS_RUNNING);
        })->with('server')->get();
    }
}