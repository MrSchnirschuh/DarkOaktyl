<?php

namespace DarkOak\Http\Controllers\Api\Application\Presets;

use DarkOak\Models\ServerPreset;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use DarkOak\Transformers\Api\Application\ServerPresetTransformer;
use DarkOak\Http\Requests\Api\Application\Presets\StoreServerPresetRequest;
use DarkOak\Http\Requests\Api\Application\Presets\UpdateServerPresetRequest;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;

class ServerPresetController extends ApplicationApiController
{
    public function index(): array
    {
        $user = $this->request->user();

        // return global presets + private presets owned by the current user
        $presets = ServerPreset::query()
            ->where(function ($q) use ($user) {
                $q->where('visibility', 'global')
                    ->orWhere(function ($q2) use ($user) {
                        $q2->where('visibility', 'private')->where('user_id', $user->id);
                    });
            })
            ->orderBy('name')
            ->get();

        return $this->fractal->collection($presets)
            ->transformWith(ServerPresetTransformer::class)
            ->toArray();
    }

    public function view(ServerPreset $preset): array
    {
        $user = $this->request->user();

        if ($preset->visibility === 'private' && $preset->user_id !== $user->id && !$user->root_admin) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $this->fractal->item($preset)
            ->transformWith(ServerPresetTransformer::class)
            ->toArray();
    }

    public function store(StoreServerPresetRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $this->request->user();

        $preset = new ServerPreset($data);
        $preset->uuid = Str::uuid()->toString();

        // If visibility is private, ensure user_id is set
        if (($data['visibility'] ?? 'global') === 'private') {
            $preset->user_id = $user->id;
        }

        $preset->save();

        return $this->fractal->item($preset)
            ->transformWith(ServerPresetTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }

    public function update(UpdateServerPresetRequest $request, ServerPreset $preset): array
    {
        $user = $this->request->user();

        if ($preset->visibility === 'private' && $preset->user_id !== $user->id && !$user->root_admin) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $data = $request->validated();

        // prevent non-root admins from changing visibility to global if they don't own
        if (isset($data['visibility']) && $data['visibility'] === 'global' && !$user->root_admin) {
            // allow only root admins to make a preset global
            $data['visibility'] = $preset->visibility;
        }

        $preset->fill($data);
        $preset->save();

        return $this->fractal->item($preset)
            ->transformWith(ServerPresetTransformer::class)
            ->toArray();
    }

    public function delete(ServerPreset $preset): Response
    {
        $user = $this->request->user();

        if ($preset->visibility === 'private' && $preset->user_id !== $user->id && !$user->root_admin) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $preset->delete();

        return $this->returnNoContent();
    }
}
