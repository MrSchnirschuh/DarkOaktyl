<?php

namespace DarkOak\Http\Controllers\Api\Client;

use DarkOak\Models\ApiKey;
use DarkOak\Facades\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use DarkOak\Exceptions\DisplayException;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;
use DarkOak\Transformers\Api\Client\ApiKeyTransformer;
use DarkOak\Http\Requests\Api\Client\Account\StoreApiKeyRequest;

class ApiKeyController extends ClientApiController
{
    /**
     * Returns all the API keys that exist for the given client.
     */
    public function index(ClientApiRequest $request): array
    {
        $user = $request->user();

        $keys = Cache::remember(
            "client.account.api-keys.{$user->id}",
            now()->addSeconds(30),
            static fn () => $user->apiKeys()
                ->where('key_type', ApiKey::TYPE_ACCOUNT)
                ->orderByDesc('created_at')
                ->get([
                    'id',
                    'identifier',
                    'memo',
                    'allowed_ips',
                    'created_at',
                    'last_used_at',
                ]),
        );

        return $this->fractal->collection($keys)
            ->transformWith(ApiKeyTransformer::class)
            ->toArray();
    }

    /**
     * Store a new API key for a user's account.
     *
     * @throws \DarkOak\Exceptions\DisplayException
     */
    public function store(StoreApiKeyRequest $request): array
    {
        if ($request->user()->apiKeys->count() >= 25) {
            throw new DisplayException('You have reached the account limit for number of API keys.');
        }

        $token = $request->user()->createToken(
            $request->input('description'),
            $request->input('allowed_ips')
        );

        Cache::forget("client.account.api-keys.{$request->user()->id}");

        Activity::event('user:api-key.create')
            ->subject($token->accessToken)
            ->property('identifier', $token->accessToken->identifier)
            ->log();

        return $this->fractal->item($token->accessToken)
            ->transformWith(ApiKeyTransformer::class)
            ->addMeta(['secret_token' => $token->plainTextToken])
            ->toArray();
    }

    /**
     * Deletes a given API key.
     */
    public function delete(ClientApiRequest $request, string $identifier): JsonResponse
    {
        /** @var \DarkOak\Models\ApiKey $key */
        $key = $request->user()->apiKeys()
            ->where('key_type', ApiKey::TYPE_ACCOUNT)
            ->where('identifier', $identifier)
            ->firstOrFail();

        Activity::event('user:api-key.delete')
            ->property('identifier', $key->identifier)
            ->log();

        $key->delete();

        Cache::forget("client.account.api-keys.{$request->user()->id}");

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}

