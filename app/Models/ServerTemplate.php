<?php

namespace DarkOak\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $category_id
 * @property int $egg_id
 * @property int $nest_id
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property string|null $image
 * @property string|null $startup_command
 * @property string|null $docker_image
 * @property int $default_memory
 * @property int $default_swap
 * @property int $default_disk
 * @property int $default_cpu
 * @property int $default_io
 * @property bool $is_active
 * @property bool $is_featured
 * @property ServerTemplateCategory|null $category
 * @property Egg|null $egg
 * @property Nest|null $nest
 */
class ServerTemplate extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'server_template';

    public const TYPE_MINECRAFT = 'minecraft';
    public const TYPE_VALHEIM = 'valheim';
    public const TYPE_CS2 = 'cs2';
    public const TYPE_GMOD = 'gmod';
    public const TYPE_RUST = 'rust';
    public const TYPE_FACTORIO = 'factorio';
    public const TYPE_TERRARIA = 'terraria';
    public const TYPE_OTHER = 'other';

    protected $table = 'server_templates';

    protected $fillable = [
        'uuid',
        'category_id',
        'name',
        'description',
        'type',
        'image',
        'egg_id',
        'nest_id',
        'startup_command',
        'docker_image',
        'default_memory',
        'default_swap',
        'default_disk',
        'default_cpu',
        'default_io',
        'environment_variables',
        'feature_limits',
        'is_active',
        'is_featured',
        'sort_order',
        'install_script',
        'pre_install_script',
        'post_install_script',
    ];

    protected $casts = [
        'environment_variables' => 'array',
        'feature_limits' => 'array',
        'default_memory' => 'integer',
        'default_swap' => 'integer',
        'default_disk' => 'integer',
        'default_cpu' => 'integer',
        'default_io' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static array $validationRules = [
        'uuid' => 'sometimes|string|size:36|unique:server_templates,uuid',
        'category_id' => 'required|exists:server_template_categories,id',
        'name' => 'required|string|max:191',
        'description' => 'nullable|string',
        'type' => 'required|string|in:minecraft,valheim,cs2,gmod,rust,factorio,terraria,other',
        'image' => 'nullable|string|max:191',
        'egg_id' => 'required|exists:eggs,id',
        'nest_id' => 'required|exists:nests,id',
        'startup_command' => 'nullable|string',
        'docker_image' => 'nullable|string|max:191',
        'default_memory' => 'required|integer|min:0',
        'default_swap' => 'required|integer|min:-1',
        'default_disk' => 'required|integer|min:0',
        'default_cpu' => 'required|integer|min:0',
        'default_io' => 'required|integer|between:10,1000',
        'environment_variables' => 'nullable|array',
        'feature_limits' => 'nullable|array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer|min:0',
        'install_script' => 'nullable|string',
        'pre_install_script' => 'nullable|string',
        'post_install_script' => 'nullable|string',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServerTemplateCategory::class, 'category_id');
    }

    public function egg(): BelongsTo
    {
        return $this->belongsTo(Egg::class, 'egg_id');
    }

    public function nest(): BelongsTo
    {
        return $this->belongsTo(Nest::class, 'nest_id');
    }

    public function getDefaultResources(): array
    {
        return [
            'memory' => $this->default_memory,
            'swap' => $this->default_swap,
            'disk' => $this->default_disk,
            'cpu' => $this->default_cpu,
            'io' => $this->default_io,
        ];
    }

    public function getEnvironmentVariables(): array
    {
        return $this->environment_variables ?? [];
    }

    public function getFeatureLimits(): array
    {
        return $this->feature_limits ?? [
            'databases' => 0,
            'allocations' => 0,
            'backups' => 0,
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_MINECRAFT => 'Minecraft',
            self::TYPE_VALHEIM => 'Valheim',
            self::TYPE_CS2 => 'CS2',
            self::TYPE_GMOD => 'Garry\'s Mod',
            self::TYPE_RUST => 'Rust',
            self::TYPE_FACTORIO => 'Factorio',
            self::TYPE_TERRARIA => 'Terraria',
            default => ucfirst($this->type),
        };
    }
}
