<?php

namespace Everest\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Everest\Models\EmailTemplate.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $subject
 * @property string $body_html
 * @property string|null $body_text
 * @property array|null $variables
 * @property bool $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class EmailTemplate extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'email_templates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'name',
        'subject',
        'body_html',
        'body_text',
        'variables',
        'enabled',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'variables' => 'array',
        'enabled' => 'boolean',
    ];

    /**
     * Get the scheduled emails that use this template.
     */
    public function scheduledEmails(): HasMany
    {
        return $this->hasMany(ScheduledEmail::class, 'template_key', 'key');
    }
}
