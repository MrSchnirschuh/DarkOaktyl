<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class UserPasskey extends Model
{
    protected $table = 'user_passkeys';

    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'credential_id',
        'public_key_credential',
        'attestation_type',
        'aaguid',
        'transports',
        'counter',
        'last_used_at',
    ];

    protected $casts = [
        'transports' => 'array',
        'last_used_at' => 'datetime',
    ];

    public static array $validationRules = [
        'uuid' => 'required|string|size:36|unique:user_passkeys,uuid',
        'user_id' => 'required|integer|exists:users,id',
        'name' => 'required|string|max:191',
        'credential_id' => 'required|string|max:255|unique:user_passkeys,credential_id',
        'public_key_credential' => 'required|string',
        'counter' => 'nullable|integer|min:0',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $passkey) {
            if (empty($passkey->uuid)) {
                $passkey->uuid = Uuid::uuid4()->toString();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
