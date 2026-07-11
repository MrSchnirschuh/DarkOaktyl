<?php

namespace DarkOak\Http\Controllers\Api\Client\Organizations;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;
use DarkOak\Models\Organization;
use DarkOak\Models\OrganizationMember;
use DarkOak\Models\User;
use DarkOak\Services\Organizations\OrganizationService;
use DarkOak\Transformers\Api\Client\OrganizationMemberTransformer;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OrganizationMemberController extends ClientApiController
{
    private OrganizationService $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        parent::__construct();
        $this->organizationService = $organizationService;
    }

    /**
     * List all members of an organization.
     */
    public function index(Request $request, string $slug): array
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();

        if (!$organization->isMember($request->user())) {
            throw new AccessDeniedHttpException('You do not have access to this organization.');
        }

        $members = $organization->members()
            ->with('user:id,name,email,avatar,created_at')
            ->orderBy('role', 'desc')
            ->orderBy('created_at')
            ->paginate($request->input('per_page', 20));

        return $this->transform($members, OrganizationMemberTransformer::class);
    }

    /**
     * Update a member's role.
     */
    public function updateRole(Request $request, string $slug, int $memberId): array|JsonResponse
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if (!$organization->isAdmin($user)) {
            throw new AccessDeniedHttpException('You do not have permission to manage members.');
        }

        $member = OrganizationMember::where('organization_id', $organization->id)
            ->where('id', $memberId)
            ->firstOrFail();

        $validated = $request->validate([
            'role' => 'required|string|in:admin,member',
        ]);

        // Can't change owner's role
        if ($member->isOwner()) {
            return new JsonResponse([
                'error' => 'Cannot change the owner\'s role. Transfer ownership first.',
            ], 422);
        }

        // Check if user can manage this role
        $currentMember = $organization->members()
            ->where('user_id', $user->id)
            ->first();

        if (!$currentMember || !$currentMember->canManageRole($validated['role'])) {
            throw new AccessDeniedHttpException('You cannot assign this role.');
        }

        $member->role = $validated['role'];
        $member->save();

        return $this->transform($member->fresh(), OrganizationMemberTransformer::class);
    }

    /**
     * Remove a member from the organization.
     */
    public function remove(Request $request, string $slug, int $memberId): JsonResponse
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        $member = OrganizationMember::where('organization_id', $organization->id)
            ->where('id', $memberId)
            ->firstOrFail();

        // Can remove self
        if ($member->user_id === $user->id) {
            if ($member->isOwner()) {
                return new JsonResponse([
                    'error' => 'The owner cannot leave. Transfer ownership first.',
                ], 422);
            }
            $this->organizationService->removeMember($organization, $user);
            return new JsonResponse([], 204);
        }

        // Need admin to remove others
        if (!$organization->isAdmin($user)) {
            throw new AccessDeniedHttpException('You do not have permission to remove members.');
        }

        // Can't remove owner
        if ($member->isOwner()) {
            return new JsonResponse([
                'error' => 'Cannot remove the owner. Transfer ownership first.',
            ], 422);
        }

        // Check role hierarchy
        $currentMember = $organization->members()
            ->where('user_id', $user->id)
            ->first();

        if (!$currentMember || $currentMember->getRoleLevel() <= $member->getRoleLevel()) {
            throw new AccessDeniedHttpException('You cannot remove this member.');
        }

        $targetUser = User::find($member->user_id);
        $this->organizationService->removeMember($organization, $targetUser);

        return new JsonResponse([], 204);
    }

    /**
     * Update member's billing/share settings.
     */
    public function updateBilling(Request $request, string $slug, int $memberId): array
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        $member = OrganizationMember::where('organization_id', $organization->id)
            ->where('id', $memberId)
            ->firstOrFail();

        // Can update own billing or need admin
        if ($member->user_id !== $user->id && !$organization->isAdmin($user)) {
            throw new AccessDeniedHttpException('You do not have permission to update billing.');
        }

        $validated = $request->validate([
            'monthly_share_amount' => 'numeric|min:0|nullable',
            'payment_method' => 'string|in:manual,stripe,paypal|nullable',
            'billing_email' => 'email|nullable',
        ]);

        $member->update($validated);

        return $this->transform($member->fresh(), OrganizationMemberTransformer::class);
    }

    /**
     * Record a payment for a member.
     */
    public function recordPayment(Request $request, string $slug, int $memberId): array
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if (!$organization->isAdmin($user)) {
            throw new AccessDeniedHttpException('You do not have permission to record payments.');
        }

        $member = OrganizationMember::where('organization_id', $organization->id)
            ->where('id', $memberId)
            ->firstOrFail();

        $member->recordPayment();

        return $this->transform($member->fresh(), OrganizationMemberTransformer::class);
    }

    /**
     * Get split billing overview.
     */
    public function splitBilling(Request $request, string $slug): array
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if (!$organization->isMember($user)) {
            throw new AccessDeniedHttpException('You do not have access to this organization.');
        }

        $totalCost = $organization->getTotalMonthlyCost();
        $memberCount = $organization->getMemberCount();
        $equalShare = $memberCount > 0 ? $totalCost / $memberCount : 0;

        $members = $organization->members()
            ->with('user:id,name,email')
            ->get()
            ->map(function ($member) use ($equalShare) {
                return [
                    'user_id' => $member->user_id,
                    'name' => $member->user->name,
                    'email' => $member->user->email,
                    'role' => $member->role,
                    'custom_share' => $member->monthly_share_amount,
                    'calculated_share' => (float) ($member->monthly_share_amount ?? $equalShare),
                    'last_payment_at' => $member->last_payment_at,
                    'is_overdue' => $member->isPaymentOverdue(),
                ];
            });

        return [
            'object' => 'split_billing',
            'attributes' => [
                'enabled' => $organization->getSplitCostsEnabled(),
                'total_monthly_cost' => $totalCost,
                'member_count' => $memberCount,
                'equal_share' => $equalShare,
                'members' => $members,
                'currency' => 'EUR',
            ],
        ];
    }

    /**
     * Set custom share amounts for members.
     */
    public function setCustomShares(Request $request, string $slug): array|JsonResponse
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if (!$organization->isAdmin($user)) {
            throw new AccessDeniedHttpException('You do not have permission to set shares.');
        }

        if (!$organization->getSplitCostsEnabled()) {
            return new JsonResponse([
                'error' => 'Split billing is not enabled for this organization.',
            ], 422);
        }

        $validated = $request->validate([
            'shares' => 'required|array',
            'shares.*.user_id' => 'required|exists:users,id',
            'shares.*.amount' => 'required|numeric|min:0',
        ]);

        $total = collect($validated['shares'])->sum('amount');
        $totalCost = $organization->getTotalMonthlyCost();

        // Allow 1% tolerance
        if (abs($total - $totalCost) > $totalCost * 0.01) {
            return new JsonResponse([
                'error' => "The sum of shares ({$total}) must equal the total monthly cost ({$totalCost}).",
            ], 422);
        }

        foreach ($validated['shares'] as $share) {
            $member = $organization->members()
                ->where('user_id', $share['user_id'])
                ->first();

            if ($member) {
                $member->updateMonthlyShare($share['amount']);
            }
        }

        return $this->splitBilling($request, $slug);
    }

    /**
     * Reset all shares to equal split.
     */
    public function resetShares(Request $request, string $slug): array
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if (!$organization->isAdmin($user)) {
            throw new AccessDeniedHttpException('You do not have permission to reset shares.');
        }

        $organization->members()->update(['monthly_share_amount' => null]);

        return $this->splitBilling($request, $slug);
    }
}