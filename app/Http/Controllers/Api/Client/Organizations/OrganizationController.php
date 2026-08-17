<?php

namespace DarkOak\Http\Controllers\Api\Client\Organizations;

use DarkOak\Models\User;
use Illuminate\Http\Request;
use DarkOak\Models\Organization;
use Illuminate\Http\JsonResponse;
use DarkOak\Services\Organizations\OrganizationService;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;
use DarkOak\Transformers\Api\Client\OrganizationTransformer;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use DarkOak\Http\Requests\Api\Client\Organizations\CreateOrganizationRequest;
use DarkOak\Http\Requests\Api\Client\Organizations\UpdateOrganizationRequest;

class OrganizationController extends ClientApiController
{
    private OrganizationService $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        parent::__construct();
        $this->organizationService = $organizationService;
    }

    /**
     * List all organizations for the authenticated user.
     */
    public function index(Request $request): array
    {
        $user = $request->user();

        $organizations = Organization::whereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['owner:id,name,email', 'members'])
        ->withCount('servers')
        ->orderBy('name')
        ->paginate($request->input('per_page', 20));

        return $this->transform($organizations, OrganizationTransformer::class);
    }

    /**
     * Get a single organization.
     */
    public function show(Request $request, string $slug): array
    {
        $organization = Organization::where('slug', $slug)
            ->with(['owner:id,name,email', 'members.user:id,name,email,avatar'])
            ->withCount('servers')
            ->firstOrFail();

        if (!$organization->isMember($request->user())) {
            throw new AccessDeniedHttpException('You do not have access to this organization.');
        }

        return $this->transform($organization, OrganizationTransformer::class);
    }

    /**
     * Create a new organization.
     */
    public function store(CreateOrganizationRequest $request): JsonResponse
    {
        $organization = $this->organizationService->create(
            $request->user(),
            $request->validated()
        );

        return new JsonResponse([
            'object' => 'organization',
            'attributes' => (new OrganizationTransformer())->transform($organization),
        ], 201);
    }

    /**
     * Update an organization.
     */
    public function update(UpdateOrganizationRequest $request, string $slug): array
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();

        if (!$organization->isAdmin($request->user())) {
            throw new AccessDeniedHttpException('You do not have permission to update this organization.');
        }

        $organization = $this->organizationService->update($organization, $request->validated());

        return $this->transform($organization, OrganizationTransformer::class);
    }

    /**
     * Delete an organization.
     */
    public function destroy(Request $request, string $slug): JsonResponse
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();

        if (!$organization->isOwner($request->user())) {
            throw new AccessDeniedHttpException('Only the owner can delete an organization.');
        }

        $this->organizationService->delete($organization);

        return new JsonResponse([], 204);
    }

    /**
     * Get organization settings.
     */
    public function settings(Request $request, string $slug): array
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();

        if (!$organization->isMember($request->user())) {
            throw new AccessDeniedHttpException('You do not have access to this organization.');
        }

        return [
            'object' => 'organization_settings',
            'attributes' => [
                'split_costs' => $organization->getSplitCostsEnabled(),
                'auto_approve_members' => $organization->getSetting('auto_approve_members', false),
                'default_member_role' => $organization->getSetting('default_member_role', Organization::ROLE_MEMBER),
                'total_monthly_cost' => $organization->getTotalMonthlyCost(),
                'member_count' => $organization->getMemberCount(),
                'cost_per_member' => $organization->getCostPerMember(),
            ],
        ];
    }

    /**
     * Update organization settings.
     */
    public function updateSettings(Request $request, string $slug): array
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();

        if (!$organization->isAdmin($request->user())) {
            throw new AccessDeniedHttpException('You do not have permission to update settings.');
        }

        $validated = $request->validate([
            'split_costs' => 'boolean|nullable',
            'auto_approve_members' => 'boolean|nullable',
            'default_member_role' => 'string|in:member,admin|nullable',
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $organization->setSetting($key, $value);
            }
        }

        return $this->settings($request, $slug);
    }

    /**
     * Leave an organization.
     */
    public function leave(Request $request, string $slug): JsonResponse
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if ($organization->isOwner($user)) {
            return new JsonResponse([
                'error' => 'The owner cannot leave the organization. Transfer ownership first.',
            ], 422);
        }

        $this->organizationService->removeMember($organization, $user);

        return new JsonResponse([], 204);
    }

    /**
     * Transfer ownership of an organization.
     */
    public function transferOwnership(Request $request, string $slug): array|JsonResponse
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if (!$organization->isOwner($user)) {
            throw new AccessDeniedHttpException('Only the owner can transfer ownership.');
        }

        $validated = $request->validate([
            'new_owner_id' => 'required|exists:users,id',
        ]);

        $newOwner = User::findOrFail($validated['new_owner_id']);

        if (!$organization->isMember($newOwner)) {
            return new JsonResponse([
                'error' => 'The new owner must be a member of the organization.',
            ], 422);
        }

        $this->organizationService->transferOwnership($organization, $newOwner);

        return $this->transform($organization->fresh(), OrganizationTransformer::class);
    }
}
