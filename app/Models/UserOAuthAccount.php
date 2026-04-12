<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOAuthAccount extends Model
{
    /**
     * OAuth provider constants.
     */
    public const PROVIDER_DISCORD = 'discord';
    public const PROVIDER_GOOGLE = 'google';

    /**
     * The table associated with the model.
     */
    protected $table = 'user_oauth_accounts';

    /**
     * A list of mass-assignable variables.
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'email',
        'provider_data',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'provider_data' => 'array',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static array $validationRules = [
        'user_id' => 'required|exists:users,id',
        'provider' => 'required|string|in:discord,google',
        'provider_id' => 'required|string',
        'email' => 'required|email',
        'provider_data' => 'nullable|array',
    ];

    /**
     * Get the user that owns this OAuth account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
