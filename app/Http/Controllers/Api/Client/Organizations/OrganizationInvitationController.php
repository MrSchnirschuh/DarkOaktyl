<?php

namespace DarkOak\Http\Controllers\Api\Client\Organizations;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;
use DarkOak\Models\Organization;
use DarkOak\Models\OrganizationInvitation;
use DarkOak\Models\User;
use DarkOak\Services\Organizations\OrganizationInvitationService;
use DarkOak\Transformers\Api\Client\OrganizationInvitationTransformer;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OrganizationInvitationController extends ClientApiController
{
    private OrganizationInvitationService $invitationService;

    public function __construct(OrganizationInvitationService $invitationService)
    {
        parent::__construct();
        $this->invitationService = $invitationService;
    }

    /**
     * List all invitations for an organization.
     */
    public function index(Request $request, string $slug): array
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();

        if (!$organization->isMember($request->user())) {
            throw new AccessDeniedHttpException('You do not have access to this organization.');
        }

        $invitations = $organization->invitations()
            ->with('invitedBy:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->transform($invitations, OrganizationInvitationTransformer::class);
    }

    /**
     * Create a new invitation.
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if (!$organization->isAdmin($user)) {
            throw new AccessDeniedHttpException('You do not have permission to invite members.');
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'string|in:admin,member|default:member',
            'message' => 'string|nullable|max:500',
            'expires_days' => 'integer|min:1|max:30|default:7',
        ]);

        // Check if already a member
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser && $organization->isMember($existingUser)) {
            return new JsonResponse([
                'error' => 'This user is already a member of the organization.',
            ], 422);
        }

        // Check for existing pending invitation
        $existingInvitation = $organization->pendingInvitations()
            ->where('email', $validated['email'])
            ->first();

        if ($existingInvitation) {
            return new JsonResponse([
                'error' => 'An invitation is already pending for this email.',
                'existing_invitation' => (new OrganizationInvitationTransformer())->transform($existingInvitation),
            ], 422);
        }

        $invitation = $this->invitationService->create($organization, $user, $validated);

        return new JsonResponse([
            'object' => 'organization_invitation',
            'attributes' => (new OrganizationInvitationTransformer())->transform($invitation),
        ], 201);
    }

    /**
     * Cancel an invitation.
     */
    public function cancel(Request $request, string $slug, int $invitationId): JsonResponse
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if (!$organization->isAdmin($user)) {
            throw new AccessDeniedHttpException('You do not have permission to cancel invitations.');
        }

        $invitation = OrganizationInvitation::where('organization_id', $organization->id)
            ->where('id', $invitationId)
            ->firstOrFail();

        if (!$invitation->isPending()) {
            return new JsonResponse([
                'error' => 'This invitation cannot be cancelled.',
            ], 422);
        }

        $invitation->delete();

        return new JsonResponse([], 204);
    }

    /**
     * Resend an invitation.
     */
    public function resend(Request $request, string $slug, int $invitationId): array|JsonResponse
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if (!$organization->isAdmin($user)) {
            throw new AccessDeniedHttpException('You do not have permission to resend invitations.');
        }

        $invitation = OrganizationInvitation::where('organization_id', $organization->id)
            ->where('id', $invitationId)
            ->firstOrFail();

        if (!$invitation->isPending()) {
            return new JsonResponse([
                'error' => 'Only pending invitations can be resent.',
            ], 422);
        }

        // Update expiration
        $invitation->expires_at = now()->addDays(7);
        $invitation->save();

        $this->invitationService->sendNotification($invitation);

        return $this->transform($invitation->fresh(), OrganizationInvitationTransformer::class);
    }

    /**
     * Accept an invitation.
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = OrganizationInvitation::where('token', $token)
            ->with('organization')
            ->firstOrFail();

        $user = $request->user();

        // Verify invitation belongs to this user
        if ($user->email !== $invitation->email) {
            return new JsonResponse([
                'error' => 'This invitation was sent to a different email address.',
            ], 403);
        }

        if ($invitation->isExpired()) {
            $invitation->markAsExpired();
            return new JsonResponse([
                'error' => 'This invitation has expired.',
            ], 410);
        }

        if (!$invitation->isPending()) {
            return new JsonResponse([
                'error' => 'This invitation has already been ' . $invitation->status . '.',
            ], 422);
        }

        $this->invitationService->accept($invitation, $user);

        return new JsonResponse([
            'message' => 'You have successfully joined the organization.',
            'organization' => [
                'id' => $invitation->organization->id,
                'name' => $invitation->organization->name,
                'slug' => $invitation->organization->slug,
            ],
        ]);
    }

    /**
     * Decline an invitation.
     */
    public function decline(Request $request, string $token): JsonResponse
    {
        $invitation = OrganizationInvitation::where('token', $token)
            ->with('organization')
            ->firstOrFail();

        $user = $request->user();

        if ($user->email !== $invitation->email) {
            return new JsonResponse([
                'error' => 'This invitation was sent to a different email address.',
            ], 403);
        }

        if ($invitation->isExpired()) {
            $invitation->markAsExpired();
            return new JsonResponse([
                'error' => 'This invitation has expired.',
            ], 410);
        }

        if (!$invitation->isPending()) {
            return new JsonResponse([
                'error' => 'This invitation has already been ' . $invitation->status . '.',
            ], 422);
        }

        $this->invitationService->decline($invitation);

        return new JsonResponse([
            'message' => 'Invitation declined.',
        ]);
    }

    /**
     * Get invitation details (for public view via token).
     */
    public function showByToken(Request $request, string $token): array
    {
        $invitation = OrganizationInvitation::where('token', $token)
            ->with('organization:id,name,description,slug,avatar')
            ->with('invitedBy:id,name,email')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            $invitation->markAsExpired();
            throw new \Exception('This invitation has expired.');
        }

        if (!$invitation->isPending()) {
            throw new \Exception('This invitation is no longer valid.');
        }

        return [
            'object' => 'organization_invitation',
            'attributes' => [
                'id' => $invitation->id,
                'organization' => [
                    'name' => $invitation->organization->name,
                    'description' => $invitation->organization->description,
                    'slug' => $invitation->organization->slug,
                    'avatar' => $invitation->organization->avatar,
                ],
                'invited_by' => [
                    'name' => $invitation->invitedBy->name,
                    'email' => $invitation->invitedBy->email,
                ],
                'email' => $invitation->email,
                'role' => $invitation->role,
                'message' => $invitation->message,
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ],
        ];
    }

    /**
     * List all pending invitations for the authenticated user.
     */
    public function userInvitations(Request $request): array
    {
        $user = $request->user();

        $invitations = OrganizationInvitation::where('email', $user->email)
            ->where('status', OrganizationInvitation::STATUS_PENDING)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('organization:id,name,slug,avatar')
            ->with('invitedBy:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->transform($invitations, OrganizationInvitationTransformer::class);
    }
}
