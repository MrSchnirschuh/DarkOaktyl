<?php

namespace DarkOak\Http\Controllers\Api\Application\Roles;

use DarkOak\Models\User;
use DarkOak\Models\AdminRole;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;
use DarkOak\Exceptions\Http\QueryValueOutOfRangeHttpException;
use DarkOak\Transformers\Api\Application\AdminRoleTransformer;
use DarkOak\Http\Requests\Api\Application\Roles\GetRoleRequest;
use DarkOak\Http\Requests\Api\Application\Roles\GetRolesRequest;
use DarkOak\Http\Requests\Api\Application\Roles\StoreRoleRequest;
use DarkOak\Http\Requests\Api\Application\Roles\DeleteRoleRequest;
use DarkOak\Http\Requests\Api\Application\Roles\UpdateRoleRequest;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;

class RoleController extends ApplicationApiController
{
    /**
     * RoleController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns an array of all roles.
     */
    public function index(GetRolesRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '20');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $roles = QueryBuilder::for(AdminRole::query())
            ->allowedFilters(['id', 'name'])
            ->allowedSorts(['id', 'name'])
            ->paginate($perPage);

        return $this->fractal->collection($roles)
            ->transformWith(AdminRoleTransformer::class)
            ->toArray();
    }

    /**
     * Returns a single role.
     */
    public function view(GetRoleRequest $request, AdminRole $role): array
    {
        return $this->fractal->item($role)
            ->transformWith(AdminRoleTransformer::class)
            ->toArray();
    }

    /**
     * Returns all of the available admin permissions assignable to users.
     */
    protected function permissions(GetRoleRequest $request): array
    {
        return [
            'object' => 'role_permissions',
            'attributes' => [
                'permissions' => AdminRole::permissions(),
            ],
        ];
    }

    /**
     * Creates a new role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'sort_id' => 99,
        ]);
        $role = AdminRole::query()->create($data);

        return $this->fractal->item($role)
            ->transformWith(AdminRoleTransformer::class)
            ->respond(JsonResponse::HTTP_CREATED);
    }

    /**
     * Updates a role.
     */
    public function update(UpdateRoleRequest $request, AdminRole $role): array
    {
        $role->update($request->validated());

        return $this->fractal->item($role)
            ->transformWith(AdminRoleTransformer::class)
            ->toArray();
    }

    /**
     * Updates the assigned permissions to a role.
     */
    public function updatePermissions(UpdateRoleRequest $request, AdminRole $role): array
    {
        dd($request->input('permissions'));

        return $this->fractal->item($role)
            ->transformWith(AdminRoleTransformer::class)
            ->toArray();
    }

    /**
     * Deletes a role.
     *
     * @throws \Exception
     */
    public function delete(DeleteRoleRequest $request, AdminRole $role): Response
    {
        // Use DB::transaction to ensure both changes happen successfully, or not at all.
        DB::transaction(function () use ($role) {
            User::where('admin_role_id', $role->id)->update(['admin_role_id' => null, 'root_admin' => false]);

            $role->delete();
        });

        return $this->returnNoContent();
    }
}

