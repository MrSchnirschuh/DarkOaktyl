<?php

namespace Everest\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Everest\Models\ScheduledEmail.
 *
 * @property int $id
 * @property string $name
 * @property string $template_key
 * @property string $trigger_type
 * @property string|null $trigger_value
 * @property string|null $event_name
 * @property array|null $recipients
 * @property array|null $template_data
 * @property bool $enabled
 * @property \Illuminate\Support\Carbon|null $last_run_at
 * @property \Illuminate\Support\Carbon|null $next_run_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ScheduledEmail extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'scheduled_emails';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'template_key',
        'trigger_type',
        'trigger_value',
        'event_name',
        'recipients',
        'template_data',
        'enabled',
        'last_run_at',
        'next_run_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'recipients' => 'array',
        'template_data' => 'array',
        'enabled' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    /**
     * Get the email template for this scheduled email.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_key', 'key');
    }
}
