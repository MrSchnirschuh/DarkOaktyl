<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $table = 'push_subscriptions';

    protected $fillable = [
        'uuid',
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'preferences',
        'last_used_at',
    ];

    protected $casts = [
        'preferences' => 'array',
        'last_used_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $subscription) {
            if (empty($subscription->uuid)) {
                $subscription->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    /**
     * Available notification event types.
     */
    public static function getAvailableEvents(): array
    {
        return [
            'server.created',
            'server.started',
            'server.stopped',
            'server.suspended',
            'server.unsuspended',
            'server.backup.completed',
            'server.backup.failed',
            'server.reinstalled',
            'billing.invoice.created',
            'billing.invoice.paid',
            'billing.invoice.overdue',
            'billing.payment.failed',
            'user.api_key.created',
            'user.password.changed',
            'organization.invited',
            'organization.role_changed',
            'autoscaling.triggered',
            'autoscaling.completed',
            'deployment.completed',
            'deployment.failed',
        ];
    }

    /**
     * Human-readable labels for events.
     */
    public static function getEventLabels(): array
    {
        return [
            'server.created' => 'Server Created',
            'server.started' => 'Server Started',
            'server.stopped' => 'Server Stopped',
            'server.suspended' => 'Server Suspended',
            'server.unsuspended' => 'Server Unsuspended',
            'server.backup.completed' => 'Backup Completed',
            'server.backup.failed' => 'Backup Failed',
            'server.reinstalled' => 'Server Reinstalled',
            'billing.invoice.created' => 'Invoice Created',
            'billing.invoice.paid' => 'Invoice Paid',
            'billing.invoice.overdue' => 'Invoice Overdue',
            'billing.payment.failed' => 'Payment Failed',
            'user.api_key.created' => 'API Key Created',
            'user.password.changed' => 'Password Changed',
            'organization.invited' => 'Organization Invitation',
            'organization.role_changed' => 'Role Changed',
            'autoscaling.triggered' => 'Auto-Scaling Triggered',
            'autoscaling.completed' => 'Auto-Scaling Completed',
            'deployment.completed' => 'Deployment Completed',
            'deployment.failed' => 'Deployment Failed',
        ];
    }

    /**
     * Event categories for grouping.
     */
    public static function getEventCategories(): array
    {
        return [
            'server' => [
                'server.created', 'server.started', 'server.stopped',
                'server.suspended', 'server.unsuspended',
                'server.backup.completed', 'server.backup.failed',
                'server.reinstalled',
            ],
            'billing' => [
                'billing.invoice.created', 'billing.invoice.paid',
                'billing.invoice.overdue', 'billing.payment.failed',
            ],
            'security' => [
                'user.api_key.created', 'user.password.changed',
                'organization.invited', 'organization.role_changed',
            ],
            'automation' => [
                'autoscaling.triggered', 'autoscaling.completed',
                'deployment.completed', 'deployment.failed',
            ],
        ];
    }

    /**
     * Get default preferences (all enabled).
     */
    public static function getDefaultPreferences(): array
    {
        return array_fill_keys(self::getAvailableEvents(), true);
    }

    /*
    | Scopes
    */

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->where(function ($q) use ($event) {
            $q->whereNull('preferences')
              ->orWhereJsonContains("preferences->{$event}", true)
              ->orWhereJsonContains("preferences->{$event}", 1);
        });
    }

    public function scopeStale($query, int $days = 30)
    {
        return $query->where('last_used_at', '<', now()->subDays($days));
    }

    /*
    | Relationships
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    | Helpers
    */

    public function touchLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function hasPreference(string $event): bool
    {
        $prefs = $this->preferences ?? [];

        return $prefs[$event] ?? true;
    }
}
