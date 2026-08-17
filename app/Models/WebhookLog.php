<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * DarkOak\Models\WebhookLog.
 *
 * @property int $id
 * @property int $webhook_id
 * @property string $event
 * @property string $payload
 * @property int $attempt
 * @property int|null $response_code
 * @property string|null $response_body
 * @property bool $success
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property Webhook $webhook
 */
class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'attempt',
        'response_code',
        'response_body',
        'success',
        'error_message',
    ];

    protected $casts = [
        'attempt' => 'integer',
        'response_code' => 'integer',
        'success' => 'boolean',
    ];

    public static array $validationRules = [
        'webhook_id' => 'required|integer|exists:webhooks,id',
        'event' => 'required|string|max:191',
        'payload' => 'required|string',
        'attempt' => 'required|integer|min:1',
        'response_code' => 'nullable|integer',
        'response_body' => 'nullable|string',
        'success' => 'required|boolean',
        'error_message' => 'nullable|string',
    ];

    /**
     * Get the webhook that owns this log.
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Get the decoded payload.
     */
    public function getDecodedPayload(): array
    {
        return json_decode($this->payload, true) ?? [];
    }

    /**
     * Scope for successful logs.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope for failed logs.
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope for specific event.
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope for recent logs.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
