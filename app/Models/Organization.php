<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $slug
 * @property string|null $avatar
 * @property int $owner_id
 * @property array|null $settings
 * @property float|null $monthly_budget
 * @property string|null $billing_address
 * @property string|null $tax_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $members_count
 * @property int|null $servers_count
 * @property \DarkOak\Models\User $owner
 * @property \Illuminate\Database\Eloquent\Collection<int, \DarkOak\Models\OrganizationMember> $members
 * @property \Illuminate\Database\Eloquent\Collection|\DarkOak\Models\OrganizationInvitation[] $invitations
 * @property \Illuminate\Database\Eloquent\Collection<int, \DarkOak\Models\Server> $servers
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereSlug($value)
 */
class Organization extends Model
{
    use HasFactory;

    public const RESOURCE_NAME = 'organization';

    protected $table = 'organizations';

    protected $fillable = [
        'name',
        'description',
        'owner_id',
        'slug',
        'avatar',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    // Constants for settings
    const SETTING_SPLIT_COSTS = 'split_costs';
    const SETTING_AUTO_APPROVE_MEMBERS = 'auto_approve_members';
    const SETTING_DEFAULT_MEMBER_ROLE = 'default_member_role';

    // Roles
    const ROLE_OWNER = 'owner';
    const ROLE_ADMIN = 'admin';
    const ROLE_MEMBER = 'member';

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class)
            ->where('status', OrganizationInvitation::STATUS_PENDING);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function isAdmin(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->whereIn('role', [self::ROLE_OWNER, self::ROLE_ADMIN])
            ->exists();
    }

    public function isMember(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function getMemberRole(User $user): ?string
    {
        $member = $this->members()
            ->where('user_id', $user->id)
            ->first();

        return $member instanceof OrganizationMember ? $member->role : null;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        $this->save();
    }

    public function getSplitCostsEnabled(): bool
    {
        return $this->getSetting(self::SETTING_SPLIT_COSTS, false);
    }

    public function getMemberCount(): int
    {
        return $this->members()->count();
    }

    public function getServerCount(): int
    {
        return $this->servers()->count();
    }

    public function getTotalMonthlyCost(): float
    {
        return $this->servers()
            ->whereNotNull('monthly_cost')
            ->sum('monthly_cost');
    }

    public function getCostPerMember(): float
    {
        $total = $this->getTotalMonthlyCost();
        $count = $this->getMemberCount();

        return $count > 0 ? $total / $count : 0;
    }

    public function calculateMonthlyCost(): float
    {
        return $this->getTotalMonthlyCost();
    }

    public function calculateSplitCostPerMember(): float
    {
        return $this->getCostPerMember();
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(OrganizationMember::class)->where('is_active', true);
    }

    public function isSplitBillingEnabled(): bool
    {
        return $this->getSplitCostsEnabled();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $organization) {
            if (empty($organization->slug)) {
                $organization->slug = \Str::slug($organization->name);
            }
        });

        static::created(function (self $organization) {
            // Automatically add owner as member with owner role
            $organization->members()->create([
                'user_id' => $organization->owner_id,
                'role' => self::ROLE_OWNER,
            ]);
        });
    }
}