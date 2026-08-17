<?php

namespace DarkOak\Http\Controllers\Api\Client\Billing;

use DarkOak\Models\User;
use DarkOak\Models\Server;
use Illuminate\Support\Str;
use DarkOak\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;
use DarkOak\Transformers\Api\Client\OrganizationTransformer;
use DarkOak\Http\Requests\Api\Client\Billing\Organizations\GetOrganizationRequest;
use DarkOak\Http\Requests\Api\Client\Billing\Organizations\GetOrganizationsRequest;
use DarkOak\Http\Requests\Api\Client\Billing\Organizations\StoreOrganizationRequest;
use DarkOak\Http\Requests\Api\Client\Billing\Organizations\DeleteOrganizationRequest;
use DarkOak\Http\Requests\Api\Client\Billing\Organizations\UpdateOrganizationRequest;

class OrganizationController extends ClientApiController
{
    /**
     * List all organizations the authenticated user belongs to.
     */
    public function index(GetOrganizationsRequest $request): array
    {
        /** @var User $user */
        $user = $request->user();

        $organizations = Organization::query()
            ->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('is_active', true);
            })
            ->orWhere('owner_id', $user->id)
            ->withCount(['members', 'servers'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->fractal->collection($organizations)
            ->transformWith(OrganizationTransformer::class)
            ->toArray();
    }

    /**
     * Show a single organization.
     */
    public function view(GetOrganizationRequest $request, Organization $organization): array
    {
        $organization->loadCount(['members', 'servers']);
        $organization->load(['members.user', 'owner']);

        return $this->fractal->item($organization)
            ->transformWith(OrganizationTransformer::class)
            ->toArray();
    }

    /**
     * Create a new organization.
     *
     * @throws \DarkOak\Exceptions\Model\DataValidationException
     * @throws \Throwable
     */
    public function store(StoreOrganizationRequest $request): array
    {
        /** @var User $user */
        $user = $request->user();

        $organization = DB::transaction(function () use ($request, $user) {
            $org = Organization::create([
                'uuid' => Str::uuid(),
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'owner_id' => $user->id,
                'is_active' => true,
                'monthly_budget' => $request->input('monthly_budget'),
                'billing_address' => $request->input('billing_address'),
                'tax_id' => $request->input('tax_id'),
            ]);

            // Create owner membership
            $org->members()->create([
                'user_id' => $user->id,
                'role' => Organization::ROLE_OWNER,
                'is_active' => true,
                'joined_at' => now(),
            ]);

            return $org;
        });

        $organization->loadCount(['members', 'servers']);

        return $this->fractal->item($organization)
            ->transformWith(OrganizationTransformer::class)
            ->toArray();
    }

    /**
     * Update an organization.
     *
     * @throws \DarkOak\Exceptions\Model\DataValidationException
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization): array
    {
        $organization->update([
            'name' => $request->input('name', $organization->name),
            'description' => $request->has('description')
                ? $request->input('description')
                : $organization->description,
            'monthly_budget' => $request->has('monthly_budget')
                ? $request->input('monthly_budget')
                : $organization->monthly_budget,
            'billing_address' => $request->has('billing_address')
                ? $request->input('billing_address')
                : $organization->billing_address,
            'tax_id' => $request->has('tax_id')
                ? $request->input('tax_id')
                : $organization->tax_id,
        ]);

        $organization->loadCount(['members', 'servers']);

        return $this->fractal->item($organization)
            ->transformWith(OrganizationTransformer::class)
            ->toArray();
    }

    /**
     * Delete an organization.
     *
     * @throws \DarkOak\Exceptions\DisplayException
     * @throws \Throwable
     */
    public function delete(DeleteOrganizationRequest $request, Organization $organization): JsonResponse
    {
        // Check if organization has servers
        if ($organization->servers()->count() > 0) {
            return new JsonResponse([
                'error' => 'Cannot delete organization with active servers. Please transfer or delete all servers first.',
            ], JsonResponse::HTTP_CONFLICT);
        }

        DB::transaction(function () use ($organization) {
            // Delete all related data
            $organization->invitations()->delete();
            $organization->members()->delete();
            $organization->delete();
        });

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Get organization billing summary.
     */
    public function billingSummary(GetOrganizationRequest $request, Organization $organization): array
    {
        $monthlyCost = $organization->calculateMonthlyCost();
        $splitPerMember = $organization->calculateSplitCostPerMember();
        $memberCount = $organization->activeMembers()->count();

        return [
            'data' => [
                'monthly_cost' => $monthlyCost,
                'split_per_member' => $splitPerMember,
                'member_count' => $memberCount,
                'is_split_enabled' => $organization->isSplitBillingEnabled(),
                'monthly_budget' => $organization->monthly_budget,
                'budget_remaining' => $organization->monthly_budget
                    ? max(0, $organization->monthly_budget - $monthlyCost)
                    : null,
            ],
        ];
    }

    /**
     * Get servers belonging to this organization.
     */
    public function servers(GetOrganizationRequest $request, Organization $organization): array
    {
        $servers = $organization->servers()
            ->with(['allocation', 'product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'data' => $servers->map(fn (Server $server) => [
                'id' => $server->uuid,
                'name' => $server->name,
                'description' => $server->description,
                'status' => $server->status,
                'price' => $server->product ? $server->product->price : 0,
                'split_billing_enabled' => $server->split_billing_enabled,
            ]),
        ];
    }
}
