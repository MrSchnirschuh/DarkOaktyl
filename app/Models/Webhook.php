<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * DarkOak\Models\Webhook.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $url
 * @property string|null $secret
 * @property array $events
 * @property bool $enabled
 * @property int|null $last_response_code
 * @property \Illuminate\Support\Carbon|null $last_sent_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Database\Eloquent\Collection|WebhookLog[] $logs
 */
class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'url',
        'secret',
        'events',
        'enabled',
        'last_response_code',
        'last_sent_at',
    ];

    protected $casts = [
        'events' => 'array',
        'enabled' => 'boolean',
        'last_response_code' => 'integer',
        'last_sent_at' => 'datetime',
    ];

    public static array $validationRules = [
        'uuid' => 'required|string|size:36|unique:webhooks,uuid',
        'name' => 'required|string|max:191',
        'url' => 'required|url|max:500|starts_with:https://',
        'secret' => 'required|string|min:16|max:255',
        'events' => 'required|array|min:1',
        'events.*' => 'string|in:server.created,server.deleted,user.registered,billing.order.completed',
        'enabled' => 'boolean',
    ];

    /**
     * Boot the model and auto-generate UUID.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Webhook $webhook) {
            if (empty($webhook->uuid)) {
                $webhook->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Get the logs for this webhook.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    /**
     * Check if this webhook listens to a specific event.
     */
    public function listensTo(string $event): bool
    {
        return in_array($event, $this->events ?? [], true);
    }

    /**
     * Generate signature for payload.
     */
    public function signPayload(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret ?? '');
    }

    /**
     * Get recent successful deliveries count.
     */
    public function recentSuccessCount(int $hours = 24): int
    {
        return $this->logs()
            ->where('success', true)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();
    }

    /**
     * Get recent failed deliveries count.
     */
    public function recentFailureCount(int $hours = 24): int
    {
        return $this->logs()
            ->where('success', false)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();
    }
}
