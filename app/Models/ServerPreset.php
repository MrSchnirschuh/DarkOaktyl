<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DarkOak\Models\ServerPreset
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property array|null $settings
 * @property int|null $port_start
 * @property int|null $port_end
 * @property string $visibility
 * @property int|null $user_id
 * @property array|null $naming
 */
class ServerPreset extends Model
{
    use SoftDeletes;

    protected $table = 'server_presets';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'settings',
        'port_start',
        'port_end',
        'visibility',
        'user_id',
        'naming',
    ];

    protected $casts = [
        'settings' => 'array',
        'naming' => 'array',
        'port_start' => 'integer',
        'port_end' => 'integer',
    ];

    public static array $validationRules = [
        'uuid' => 'required|string|size:36|unique:server_presets,uuid',
        'name' => 'required|string|max:191',
        'visibility' => 'in:global,private',
        'port_start' => 'nullable|integer|min:1|max:65535',
        'port_end' => 'nullable|integer|min:1|max:65535|gte:port_start',
        'user_id' => 'nullable|exists:users,id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
