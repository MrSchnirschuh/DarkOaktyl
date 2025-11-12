<?php

namespace Everest\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    protected $table = 'email_templates';

    protected $fillable = [
        'uuid',
        'key',
        'name',
        'description',
        'subject',
        'content',
        'locale',
        'is_enabled',
        'theme_id',
        'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'metadata' => 'array',
    ];

    public static array $validationRules = [
        'uuid' => 'sometimes|string|size:36|unique:email_templates,uuid',
        'key' => 'required|string|max:191|unique:email_templates,key',
        'name' => 'required|string|max:191',
        'description' => 'nullable|string',
        'subject' => 'required|string|max:191',
        'content' => 'required|string',
        'locale' => 'required|string|max:12',
        'is_enabled' => 'boolean',
        'theme_id' => 'nullable|exists:email_themes,id',
        'metadata' => 'nullable|array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(EmailTheme::class, 'theme_id');
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(EmailTrigger::class, 'template_id');
    }
}
