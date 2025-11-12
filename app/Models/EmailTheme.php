<?php

namespace DarkOak\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTheme extends Model
{
    protected $table = 'email_themes';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
        'body_color',
        'text_color',
        'muted_text_color',
        'button_color',
        'button_text_color',
        'logo_url',
        'footer_text',
        'is_default',
        'meta',
        'variant_mode',
        'light_palette',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'meta' => 'array',
        'light_palette' => 'array',
    ];

    protected $attributes = [
        'variant_mode' => 'single',
    ];

    public static array $validationRules = [
        'uuid' => 'sometimes|string|size:36|unique:email_themes,uuid',
        'name' => 'required|string|max:191',
        'description' => 'nullable|string',
        'primary_color' => 'required|string|max:8',
        'secondary_color' => 'required|string|max:8',
        'accent_color' => 'required|string|max:8',
        'background_color' => 'required|string|max:8',
        'body_color' => 'required|string|max:8',
        'text_color' => 'required|string|max:8',
        'muted_text_color' => 'required|string|max:8',
        'button_color' => 'required|string|max:8',
        'button_text_color' => 'required|string|max:8',
        'logo_url' => 'nullable|url',
        'footer_text' => 'nullable|string',
        'is_default' => 'boolean',
        'meta' => 'nullable|array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function templates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class, 'theme_id');
    }
}

