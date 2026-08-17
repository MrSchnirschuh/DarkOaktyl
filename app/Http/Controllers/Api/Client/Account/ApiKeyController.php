<?php

namespace DarkOak\Http\Controllers\Api\Client\Account;

use DarkOak\Models\ApiKey;
use DarkOak\Facades\Activity;
use DarkOak\Models\ApiKeyScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use DarkOak\Exceptions\DisplayException;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;
use DarkOak\Transformers\Api\Client\ApiKeyTransformer;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;
use DarkOak\Http\Requests\Api\Client\Account\StoreApiKeyRequest;
use DarkOak\Http\Requests\Api\Client\Account\UpdateApiKeyScopesRequest;

class ApiKeyController extends ClientApiController
{
    /**
     * Returns all the API keys that exist for the given client.
     * Inklusive Scopes.
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
                    'scopes',
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
     * Unterstützt optionale Scope-Einschränkungen.
     *
     * @throws DisplayException
     */
    public function store(StoreApiKeyRequest $request): array
    {
        if ($request->user()->apiKeys->count() >= 25) {
            throw new DisplayException('You have reached the account limit for number of API keys.');
        }

        $scopes = $request->input('scopes', []);

        // Validiere Scopes
        if (!empty($scopes)) {
            $invalidScopes = ApiKeyScope::validateScopes($scopes);
            if (!empty($invalidScopes)) {
                throw new DisplayException('Invalid scopes: ' . implode(', ', $invalidScopes));
            }
        }

        $token = $request->user()->createToken(
            $request->input('description'),
            $request->input('allowed_ips')
        );

        // Speichere Scopes
        if (!empty($scopes)) {
            $token->accessToken->scopes = $scopes;
            $token->accessToken->save();
        }

        Cache::forget("client.account.api-keys.{$request->user()->id}");

        Activity::event('user:api-key.create')
            ->subject($token->accessToken)
            ->property('identifier', $token->accessToken->identifier)
            ->property('scopes', $scopes)
            ->log();

        return $this->fractal->item($token->accessToken)
            ->transformWith(ApiKeyTransformer::class)
            ->addMeta(['secret_token' => $token->plainTextToken])
            ->toArray();
    }

    /**
     * Updates the scopes of an existing API key.
     */
    public function updateScopes(UpdateApiKeyScopesRequest $request, string $identifier): array
    {
        /** @var ApiKey $key */
        $key = $request->user()->apiKeys()
            ->where('key_type', ApiKey::TYPE_ACCOUNT)
            ->where('identifier', $identifier)
            ->firstOrFail();

        $scopes = $request->input('scopes', []);

        // Validiere Scopes
        if (!empty($scopes)) {
            $invalidScopes = ApiKeyScope::validateScopes($scopes);
            if (!empty($invalidScopes)) {
                throw new DisplayException('Invalid scopes: ' . implode(', ', $invalidScopes));
            }
        }

        $key->scopes = $scopes;
        $key->save();

        Cache::forget("client.account.api-keys.{$request->user()->id}");

        Activity::event('user:api-key.update')
            ->subject($key)
            ->property('identifier', $key->identifier)
            ->property('scopes', $scopes)
            ->log();

        return $this->fractal->item($key)
            ->transformWith(ApiKeyTransformer::class)
            ->toArray();
    }

    /**
     * Deletes a given API key.
     */
    public function delete(ClientApiRequest $request, string $identifier): JsonResponse
    {
        /** @var ApiKey $key */
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

    /**
     * Gibt alle verfügbaren Scopes zurück (für UI).
     */
    public function listScopes(): array
    {
        return [
            'object' => 'list',
            'data' => ApiKeyScope::groups(),
        ];
    }

    /**
     * Gibt einen einzelnen API-Key mit allen Details zurück.
     */
    public function show(ClientApiRequest $request, string $identifier): array
    {
        /** @var ApiKey $key */
        $key = $request->user()->apiKeys()
            ->where('key_type', ApiKey::TYPE_ACCOUNT)
            ->where('identifier', $identifier)
            ->firstOrFail();

        return $this->fractal->item($key)
            ->transformWith(ApiKeyTransformer::class)
            ->toArray();
    }
}
