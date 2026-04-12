<?php

namespace DarkOak\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerTemplateCategory extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'server_template_category';

    protected $table = 'server_template_categories';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public static array $validationRules = [
        'uuid' => 'sometimes|string|size:36|unique:server_template_categories,uuid',
        'name' => 'required|string|max:191',
        'description' => 'nullable|string',
        'icon' => 'nullable|string|max:191',
        'sort_order' => 'integer|min:0',
        'is_active' => 'boolean',
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
        return $this->hasMany(ServerTemplate::class, 'category_id');
    }

    public function activeTemplates(): HasMany
    {
        return $this->hasMany(ServerTemplate::class, 'category_id')->where('is_active', true);
    }
}
