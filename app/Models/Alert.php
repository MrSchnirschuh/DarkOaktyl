<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DarkOak\Models\Alert
 *
 * @property int $id
 * @property string $uuid
 * @property string $title
 * @property string $message
 * @property string $type
 * @property string $target
 * @property int|null $user_id
 * @property bool $is_persistent
 * @property array|null $dismissed_by
 * @property \Carbon\Carbon|null $starts_at
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User|null $user
 */
class Alert extends Model
{
    use HasFactory;

    /**
     * Alert types.
     */
    public const TYPE_INFO = 'info';
    public const TYPE_WARNING = 'warning';
    public const TYPE_ERROR = 'error';
    public const TYPE_SECURITY = 'security';

    /**
     * Alert targets.
     */
    public const TARGET_GLOBAL = 'global';
    public const TARGET_ADMIN = 'admin';
    public const TARGET_USER = 'user';

    protected $table = 'alerts';

    protected $fillable = [
        'uuid',
        'title',
        'message',
        'type',
        'target',
        'user_id',
        'is_persistent',
        'dismissed_by',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'is_persistent' => 'boolean',
        'dismissed_by' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static array $validationRules = [
        'title' => 'required|string|max:100',
        'message' => 'required|string|max:1000',
        'type' => 'required|string|in:info,warning,error,security',
        'target' => 'required|string|in:global,admin,user',
        'user_id' => 'nullable|integer|exists:users,id',
        'is_persistent' => 'boolean',
        'dismissed_by' => 'nullable|array',
        'starts_at' => 'nullable|date',
        'expires_at' => 'nullable|date|after:starts_at',
    ];

    /**
     * Get the user associated with this alert (if user-specific).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the alert is active.
     */
    public function isActive(): bool
    {
        $now = now();

        // Check if alert has started
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        // Check if alert has expired
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the alert has been dismissed by a user.
     */
    public function isDismissedBy(int $userId): bool
    {
        if (!$this->dismissed_by) {
            return false;
        }

        return in_array($userId, $this->dismissed_by);
    }

    /**
     * Mark the alert as dismissed by a user.
     */
    public function dismissBy(int $userId): void
    {
        $dismissed = $this->dismissed_by ?? [];

        if (!in_array($userId, $dismissed)) {
            $dismissed[] = $userId;
            $this->update(['dismissed_by' => $dismissed]);
        }
    }

    /**
     * Scope to get active alerts.
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('starts_at')
                ->orWhere('starts_at', '<=', now());
        })->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now());
        });
    }

    /**
     * Scope to get alerts for a specific user.
     */
    public function scopeForUser($query, int $userId, bool $isAdmin = false)
    {
        return $query->where(function ($q) use ($userId, $isAdmin) {
            // Global alerts
            $q->where('target', self::TARGET_GLOBAL);

            // Admin-only alerts
            if ($isAdmin) {
                $q->orWhere('target', self::TARGET_ADMIN);
            }

            // User-specific alerts
            $q->orWhere(function ($sq) use ($userId) {
                $sq->where('target', self::TARGET_USER)
                    ->where('user_id', $userId);
            });
        });
    }

    /**
     * Scope to get non-dismissed alerts for a user.
     */
    public function scopeNotDismissedBy($query, int $userId)
    {
        return $query->whereRaw('NOT JSON_CONTAINS(COALESCE(dismissed_by, "[]"), ?)', [json_encode($userId)]);
    }

    /**
     * Get all valid alert types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_INFO,
            self::TYPE_WARNING,
            self::TYPE_ERROR,
            self::TYPE_SECURITY,
        ];
    }

    /**
     * Get all valid alert targets.
     */
    public static function getTargets(): array
    {
        return [
            self::TARGET_GLOBAL,
            self::TARGET_ADMIN,
            self::TARGET_USER,
        ];
    }

    /**
     * Get the color class for the alert type.
     */
    public function getColorClass(): string
    {
        return match ($this->type) {
            self::TYPE_INFO => 'blue',
            self::TYPE_WARNING => 'yellow',
            self::TYPE_ERROR => 'red',
            self::TYPE_SECURITY => 'red',
            default => 'blue',
        };
    }

    /**
     * Check if alert is a security alert.
     */
    public function isSecurity(): bool
    {
        return $this->type === self::TYPE_SECURITY;
    }
}
