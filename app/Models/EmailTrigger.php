<?php

namespace Everest\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTrigger extends Model
{
    public const TYPE_EVENT = 'event';
    public const TYPE_SCHEDULE = 'schedule';
    public const TYPE_RESOURCE = 'resource';

    public const SCHEDULE_ONCE = 'once';
    public const SCHEDULE_RECURRING = 'recurring';

    protected $table = 'email_triggers';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'trigger_type',
        'schedule_type',
        'event_key',
        'schedule_at',
        'cron_expression',
        'timezone',
        'template_id',
        'payload',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_active' => 'boolean',
        'schedule_at' => 'datetime',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public static array $validationRules = [
        'uuid' => 'sometimes|string|size:36|unique:email_triggers,uuid',
        'name' => 'required|string|max:191',
        'description' => 'nullable|string',
        'trigger_type' => 'required|string|in:event,schedule,resource',
        'schedule_type' => 'nullable|string|in:once,recurring',
        'event_key' => 'nullable|string|max:191',
        'schedule_at' => 'nullable|date',
        'cron_expression' => 'nullable|string|max:191',
        'timezone' => 'required|string|max:64',
        'template_id' => 'required|exists:email_templates,id',
        'payload' => 'nullable|array',
        'is_active' => 'boolean',
        'last_run_at' => 'nullable|date',
        'next_run_at' => 'nullable|date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }
}
