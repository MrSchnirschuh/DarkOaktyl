<?php

namespace DarkOak\Http\Controllers\Api\Client;

use DarkOak\Models\Server;
use DarkOak\Models\ServerGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use DarkOak\Exceptions\DisplayException;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;
use DarkOak\Http\Requests\Api\Client\ServerGroups\StoreServerGroupRequest;
use DarkOak\Http\Requests\Api\Client\ServerGroups\UpdateServerGroupRequest;
use DarkOak\Http\Requests\Api\Client\ServerGroups\ServerGroupActionRequest;
use DarkOak\Transformers\Api\Client\ServerGroupTransformer;

class ServerGroupController extends ClientApiController
{
    /**
     * List all server groups for the authenticated user.
     */
    public function index(ClientApiRequest $request): array
    {
        $groups = Cache::remember(
            "client.server-groups.{$request->user()->id}",
            now()->addMinutes(5),
            fn() => $request->user()->serverGroups()->with('servers')->get()
        );

        return $this->fractal->collection($groups)
            ->transformWith(ServerGroupTransformer::class)
            ->toArray();
    }

    /**
     * Create a new server group.
     */
    public function store(StoreServerGroupRequest $request): array
    {
        $this->checkGroupLimit($request->user()->id);

        $group = ServerGroup::create([
            'owner_id' => $request->user()->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'color' => $request->input('color'),
            'icon' => $request->input('icon'),
        ]);

        Cache::forget("client.server-groups.{$request->user()->id}");

        return $this->fractal->item($group)
            ->transformWith(ServerGroupTransformer::class)
            ->toArray();
    }

    /**
     * Update a server group.
     */
    public function update(UpdateServerGroupRequest $request, int $id): array
    {
        $group = $this->authorizeGroup($id, $request->user()->id);

        $group->update($request->only(['name', 'description', 'color', 'icon']));

        Cache::forget("client.server-groups.{$request->user()->id}");

        return $this->fractal->item($group->fresh())
            ->transformWith(ServerGroupTransformer::class)
            ->toArray();
    }

    /**
     * Delete a server group.
     */
    public function delete(ClientApiRequest $request, int $id): JsonResponse
    {
        $group = $this->authorizeGroup($id, $request->user()->id);

        // Remove group association from all servers in this group
        Server::where('group_id', $group->id)->update(['group_id' => null]);

        $group->delete();

        Cache::forget("client.server-groups.{$request->user()->id}");

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Add a server to a group.
     */
    public function add(ServerGroupActionRequest $request, int $id): JsonResponse
    {
        $group = $this->authorizeGroup($id, $request->user()->id);

        $server = Server::where('uuid', $request->input('server_uuid'))
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();

        if ($server->group_id !== null && $server->group_id !== $group->id) {
            throw new DisplayException('Server is already in another group.');
        }

        $server->update(['group_id' => $group->id]);

        Cache::forget("client.server-groups.{$request->user()->id}");

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Remove a server from a group.
     */
    public function remove(ServerGroupActionRequest $request, int $id): JsonResponse
    {
        $group = $this->authorizeGroup($id, $request->user()->id);

        $server = Server::where('uuid', $request->input('server_uuid'))
            ->where('owner_id', $request->user()->id)
            ->where('group_id', $group->id)
            ->firstOrFail();

        $server->update(['group_id' => null]);

        Cache::forget("client.server-groups.{$request->user()->id}");

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Authorize that the group belongs to the user.
     *
     * @throws DisplayException
     */
    private function authorizeGroup(int $groupId, int $userId): ServerGroup
    {
        $group = ServerGroup::findOrFail($groupId);

        if ($group->user_id !== $userId) {
            throw new DisplayException('You do not have permission to access this group.');
        }

        return $group;
    }

    /**
     * Check that the user hasn't exceeded their group limit.
     *
     * @throws DisplayException
     */
    private function checkGroupLimit(int $userId): void
    {
        $limit = config('modules.server_groups.max_groups_per_user', 10);
        $current = ServerGroup::where('owner_id', $userId)->count();

        if ($current >= $limit) {
            throw new DisplayException("You have reached the maximum number of server groups ({$limit}).");
        }
    }
}