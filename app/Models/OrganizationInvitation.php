<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $email
 * @property string $token
 * @property string $role
 * @property string $status
 * @property int|null $invited_by
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $declined_at
 * @property string|null $message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class OrganizationInvitation extends Model
{
    use HasFactory;

    public const RESOURCE_NAME = 'organization_invitation';

    protected $table = 'organization_invitations';

    protected $fillable = [
        'organization_id',
        'email',
        'token',
        'role',
        'invited_by',
        'expires_at',
        'status',
        'accepted_at',
        'declined_at',
        'message',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';
    const STATUS_EXPIRED = 'expired';

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    public function markAsAccepted(): void
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->accepted_at = now();
        $this->save();
    }

    public function markAsDeclined(): void
    {
        $this->status = self::STATUS_DECLINED;
        $this->declined_at = now();
        $this->save();
    }

    public function markAsExpired(): void
    {
        $this->status = self::STATUS_EXPIRED;
        $this->save();
    }

    public function generateToken(): string
    {
        $this->token = hash('sha256', $this->organization_id . $this->email . now() . random_bytes(16));
        return $this->token;
    }

    public function getInviteUrl(): string
    {
        return url('/organizations/join/' . $this->token);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $invitation) {
            if (empty($invitation->token)) {
                $invitation->generateToken();
            }

            if (empty($invitation->expires_at)) {
                // Default: 7 days
                $invitation->expires_at = now()->addDays(7);
            }

            if (empty($invitation->status)) {
                $invitation->status = self::STATUS_PENDING;
            }
        });
    }
}