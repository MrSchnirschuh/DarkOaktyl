<?php

namespace DarkOak\Services\Organizations;

use DarkOak\Models\User;
use Illuminate\Support\Str;
use DarkOak\Models\Organization;
use Illuminate\Support\Facades\DB;
use DarkOak\Models\OrganizationMember;

class OrganizationService
{
    /**
     * Create a new organization.
     */
    public function create(User $owner, array $data): Organization
    {
        return DB::transaction(function () use ($owner, $data) {
            $slug = $data['slug'] ?? Str::slug($data['name']);

            // Ensure unique slug
            $baseSlug = $slug;
            $counter = 1;
            while (Organization::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            $organization = Organization::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'owner_id' => $owner->id,
                'slug' => $slug,
                'avatar' => $data['avatar'] ?? null,
                'settings' => [
                    'split_costs' => $data['split_costs'] ?? false,
                    'auto_approve_members' => $data['auto_approve_members'] ?? false,
                    'default_member_role' => Organization::ROLE_MEMBER,
                ],
            ]);

            return $organization;
        });
    }

    /**
     * Update an organization.
     */
    public function update(Organization $organization, array $data): Organization
    {
        return DB::transaction(function () use ($organization, $data) {
            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];

                // Update slug if name changed and slug not explicitly provided
                if (empty($data['slug'])) {
                    $slug = Str::slug($data['name']);
                    // Ensure unique slug
                    $baseSlug = $slug;
                    $counter = 1;
                    while (Organization::where('slug', $slug)->where('id', '!=', $organization->id)->exists()) {
                        $slug = $baseSlug . '-' . $counter++;
                    }
                    $updateData['slug'] = $slug;
                }
            }

            if (isset($data['slug'])) {
                $updateData['slug'] = $data['slug'];
            }

            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            if (isset($data['avatar'])) {
                $updateData['avatar'] = $data['avatar'];
            }

            if (!empty($updateData)) {
                $organization->update($updateData);
            }

            return $organization->fresh();
        });
    }

    /**
     * Delete an organization.
     */
    public function delete(Organization $organization): bool
    {
        return DB::transaction(function () use ($organization) {
            // Servers will be handled by the onDelete constraint
            return $organization->delete();
        });
    }

    /**
     * Add a member to the organization.
     */
    public function addMember(Organization $organization, User $user, string $role = Organization::ROLE_MEMBER): OrganizationMember
    {
        return DB::transaction(function () use ($organization, $user, $role) {
            $member = OrganizationMember::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => $role,
                    'joined_at' => now(),
                ]
            );

            return $member;
        });
    }

    /**
     * Remove a member from the organization.
     */
    public function removeMember(Organization $organization, User $user): bool
    {
        return DB::transaction(function () use ($organization, $user) {
            $member = OrganizationMember::where('organization_id', $organization->id)
                ->where('user_id', $user->id)
                ->first();

            if ($member) {
                // If member owns servers in this org, transfer them to org owner
                $user->servers()
                    ->where('organization_id', $organization->id)
                    ->update(['user_id' => $organization->owner_id]);

                $member->delete();

                return true;
            }

            return false;
        });
    }

    /**
     * Transfer ownership to another member.
     */
    public function transferOwnership(Organization $organization, User $newOwner): void
    {
        DB::transaction(function () use ($organization, $newOwner) {
            // Ensure new owner is a member
            $this->addMember($organization, $newOwner, Organization::ROLE_OWNER);

            // Update old owner to admin
            $oldOwnerMember = OrganizationMember::where('organization_id', $organization->id)
                ->where('user_id', $organization->owner_id)
                ->first();

            if ($oldOwnerMember) {
                $oldOwnerMember->role = Organization::ROLE_ADMIN;
                $oldOwnerMember->save();
            }

            // Update organization owner
            $organization->owner_id = $newOwner->id;
            $organization->save();
        });
    }

    /**
     * Update member role.
     */
    public function updateMemberRole(Organization $organization, User $user, string $role): bool
    {
        $member = OrganizationMember::where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$member) {
            return false;
        }

        $member->role = $role;

        return $member->save();
    }

    /**
     * Calculate equal share for all members.
     */
    public function calculateEqualShares(Organization $organization): array
    {
        $totalCost = $organization->getTotalMonthlyCost();
        $memberCount = $organization->getMemberCount();

        if ($memberCount === 0) {
            return [];
        }

        $equalShare = $totalCost / $memberCount;

        $shares = [];
        foreach ($organization->members as $member) {
            $shares[$member->user_id] = $equalShare;
        }

        return $shares;
    }

    /**
     * Reset all shares to equal split.
     */
    public function resetShares(Organization $organization): void
    {
        $organization->members()
            ->update(['monthly_share_amount' => null]);
    }

    /**
     * Set custom share for a member.
     */
    public function setMemberShare(OrganizationMember $member, float $amount): void
    {
        $member->monthly_share_amount = $amount;
        $member->save();
    }
}
