<?php

namespace DarkOak\Services\Organizations;

use DarkOak\Models\User;
use DarkOak\Models\Organization;
use Illuminate\Support\Facades\DB;
use DarkOak\Models\OrganizationMember;
use DarkOak\Models\OrganizationInvitation;
use Illuminate\Support\Facades\Notification;
use DarkOak\Notifications\OrganizationInvitationNotification;

class OrganizationInvitationService
{
    /**
     * Create a new invitation.
     */
    public function create(Organization $organization, User $invitedBy, array $data): OrganizationInvitation
    {
        return DB::transaction(function () use ($organization, $invitedBy, $data) {
            $expiresDays = $data['expires_days'] ?? 7;

            $invitation = OrganizationInvitation::create([
                'organization_id' => $organization->id,
                'email' => $data['email'],
                'role' => $data['role'] ?? Organization::ROLE_MEMBER,
                'invited_by' => $invitedBy->id,
                'expires_at' => now()->addDays($expiresDays),
                'message' => $data['message'] ?? null,
                'status' => OrganizationInvitation::STATUS_PENDING,
            ]);

            $this->sendNotification($invitation);

            return $invitation;
        });
    }

    /**
     * Send invitation notification.
     */
    public function sendNotification(OrganizationInvitation $invitation): void
    {
        // Send email notification
        // Note: In a real implementation, you'd have a notification class
        // Notification::route('mail', $invitation->email)
        //     ->notify(new OrganizationInvitationNotification($invitation));
    }

    /**
     * Accept an invitation.
     */
    public function accept(OrganizationInvitation $invitation, User $user): OrganizationMember
    {
        return DB::transaction(function () use ($invitation, $user) {
            // Mark invitation as accepted
            $invitation->markAsAccepted();

            // Add user to organization
            $member = OrganizationMember::firstOrCreate(
                [
                    'organization_id' => $invitation->organization_id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => $invitation->role,
                    'joined_at' => now(),
                    'billing_email' => $user->email,
                ]
            );

            // If already existed, update role to invitation role
            if (!$member->wasRecentlyCreated) {
                $member->role = $invitation->role;
                $member->save();
            }

            return $member;
        });
    }

    /**
     * Decline an invitation.
     */
    public function decline(OrganizationInvitation $invitation): void
    {
        $invitation->markAsDeclined();
    }

    /**
     * Cancel an invitation.
     */
    public function cancel(OrganizationInvitation $invitation): bool
    {
        if (!$invitation->isPending()) {
            return false;
        }

        return $invitation->delete();
    }

    /**
     * Get valid invitation by token.
     */
    public function getValidInvitation(string $token): ?OrganizationInvitation
    {
        $invitation = OrganizationInvitation::where('token', $token)->first();

        if (!$invitation) {
            return null;
        }

        if ($invitation->isExpired()) {
            $invitation->markAsExpired();

            return null;
        }

        if (!$invitation->isPending()) {
            return null;
        }

        return $invitation;
    }

    /**
     * Clean up expired invitations.
     */
    public function cleanupExpired(): int
    {
        return OrganizationInvitation::where('status', OrganizationInvitation::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->update(['status' => OrganizationInvitation::STATUS_EXPIRED]);
    }

    /**
     * Check if a user has a pending invitation.
     */
    public function hasPendingInvitation(Organization $organization, string $email): bool
    {
        return $organization->pendingInvitations()
            ->where('email', $email)
            ->exists();
    }

    /**
     * Get or create invitation.
     */
    public function getOrCreate(Organization $organization, User $invitedBy, string $email, string $role = 'member'): OrganizationInvitation
    {
        // Check for existing valid invitation
        $existing = $organization->invitations()
            ->where('email', $email)
            ->where('status', OrganizationInvitation::STATUS_PENDING)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existing) {
            assert($existing instanceof OrganizationInvitation);

            return $existing;
        }

        // Create new invitation
        return $this->create($organization, $invitedBy, [
            'email' => $email,
            'role' => $role,
        ]);
    }
}
