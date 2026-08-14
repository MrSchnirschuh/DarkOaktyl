<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property string $role
 * @property \Illuminate\Support\Carbon|null $joined_at
 * @property float|null $monthly_share_amount
 * @property string|null $payment_method
 * @property string|null $billing_email
 * @property \Illuminate\Support\Carbon|null $last_payment_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \DarkOak\Models\User $user
 * @property-read \DarkOak\Models\Organization $organization
 * @method bool isOwner()
 * @method bool isAdmin()
 * @method int getRoleLevel()
 * @method bool canManageRole(string $role)
 * @method void updateMonthlyShare(float $amount)
 * @method void recordPayment()
 * @method float getCurrentShare()
 * @method bool isPaymentOverdue()
 */
class OrganizationMember extends Model
{
    use HasFactory;

    public const RESOURCE_NAME = 'organization_member';

    protected $table = 'organization_members';

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'joined_at',
        'monthly_share_amount',
        'payment_method',
        'billing_email',
        'last_payment_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'monthly_share_amount' => 'decimal:2',
    ];

    protected $attributes = [
        'role' => Organization::ROLE_MEMBER,
    ];

    // Payment methods
    const PAYMENT_MANUAL = 'manual';
    const PAYMENT_STRIPE = 'stripe';
    const PAYMENT_PAYPAL = 'paypal';

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwner(): bool
    {
        return $this->role === Organization::ROLE_OWNER;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [Organization::ROLE_OWNER, Organization::ROLE_ADMIN]);
    }

    public function getRoleLevel(): int
    {
        return match ($this->role) {
            Organization::ROLE_OWNER => 3,
            Organization::ROLE_ADMIN => 2,
            Organization::ROLE_MEMBER => 1,
            default => 0,
        };
    }

    public function canManageRole(string $role): bool
    {
        $targetLevel = match ($role) {
            Organization::ROLE_OWNER => 3,
            Organization::ROLE_ADMIN => 2,
            Organization::ROLE_MEMBER => 1,
            default => 0,
        };

        return $this->getRoleLevel() > $targetLevel;
    }

    public function updateMonthlyShare(float $amount): void
    {
        $this->monthly_share_amount = $amount;
        $this->save();
    }

    public function recordPayment(): void
    {
        $this->last_payment_at = now();
        $this->save();
    }

    public function getCurrentShare(): float
    {
        // If manually set, use that
        if ($this->monthly_share_amount !== null) {
            return (float) $this->monthly_share_amount;
        }

        // Otherwise calculate equal split
        $total = $this->organization->getTotalMonthlyCost();
        $memberCount = $this->organization->getMemberCount();

        return $memberCount > 0 ? $total / $memberCount : 0;
    }

    public function isPaymentOverdue(): bool
    {
        if (!$this->organization->getSplitCostsEnabled()) {
            return false;
        }

        // Payment is due monthly
        return $this->last_payment_at === null
            || $this->last_payment_at->diffInDays(now()) > 30;
    }
}