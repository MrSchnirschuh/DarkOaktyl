<?php

namespace DarkOak\Http\Controllers\Api\Client;

use DarkOak\Facades\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;
use DarkOak\Transformers\Api\Client\UserSSHKeyTransformer;
use DarkOak\Http\Requests\Api\Client\Account\StoreSSHKeyRequest;

class SSHKeyController extends ClientApiController
{
    /**
     * Returns all the SSH keys that have been configured for the logged-in
     * user account.
     */
    public function index(ClientApiRequest $request): array
    {
        $user = $request->user();

        $keys = Cache::remember(
            "client.account.ssh-keys.{$user->id}",
            now()->addSeconds(30),
            static fn () => $user->sshKeys()
                ->orderByDesc('created_at')
                ->get([
                    'id',
                    'name',
                    'public_key',
                    'fingerprint',
                    'created_at',
                ]),
        );

        return $this->fractal->collection($keys)
            ->transformWith(UserSSHKeyTransformer::class)
            ->toArray();
    }

    /**
     * Stores a new SSH key for the authenticated user's account.
     */
    public function store(StoreSSHKeyRequest $request): array
    {
        $model = $request->user()->sshKeys()->create([
            'name' => $request->input('name'),
            'public_key' => $request->getPublicKey(),
            'fingerprint' => $request->getKeyFingerprint(),
        ]);

        Cache::forget("client.account.ssh-keys.{$request->user()->id}");

        Activity::event('user:ssh-key.create')
            ->subject($model)
            ->property('fingerprint', $request->getKeyFingerprint())
            ->log();

        return $this->fractal->item($model)
            ->transformWith(UserSSHKeyTransformer::class)
            ->toArray();
    }

    /**
     * Deletes an SSH key from the user's account.
     */
    public function delete(ClientApiRequest $request): JsonResponse
    {
        $this->validate($request, ['fingerprint' => ['required', 'string']]);

        $key = $request->user()->sshKeys()
            ->where('fingerprint', $request->input('fingerprint'))
            ->first();

        if (!is_null($key)) {
            $key->delete();

            Activity::event('user:ssh-key.delete')
                ->subject($key)
                ->property('fingerprint', $key->fingerprint)
                ->log();

            Cache::forget("client.account.ssh-keys.{$request->user()->id}");
        }

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}

