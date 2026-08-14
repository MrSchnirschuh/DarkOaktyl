<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DarkOak\Models\UserPasskey.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $credential_id
 * @property string $public_key
 * @property string|null $attestation_data
 * @property array|null $transports
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \DarkOak\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|UserPasskey newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserPasskey newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserPasskey query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserPasskey whereAttestationData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPasskey whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPasskey whereCredentialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPasskey whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPasskey whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPasskey whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPasskey wherePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPasskey whereTransports($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPasskey whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPasskey whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPasskey whereUserId($value)
 *
 * @mixin \Eloquent
 *
 */
class UserPasskey extends Model
{
    public const RESOURCE_NAME = 'passkey';

    protected $table = 'user_passkeys';

    protected $fillable = [
        'name',
        'credential_id',
        'public_key',
        'attestation_data',
        'transports',
        'type',
        'last_used_at',
    ];

    protected $casts = [
        'transports' => 'array',
        'last_used_at' => 'datetime',
    ];

    public static array $validationRules = [
        'name' => ['required', 'string', 'max:255'],
        'credential_id' => ['required', 'string'],
        'public_key' => ['required', 'string'],
        'attestation_data' => ['nullable', 'string'],
        'transports' => ['nullable', 'json'],
        'type' => ['required', 'string', 'max:50'],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}