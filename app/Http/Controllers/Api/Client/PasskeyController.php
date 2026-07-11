<?php

namespace DarkOak\Http\Controllers\Api\Client;

use DarkOak\Facades\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;
use DarkOak\Transformers\Api\Client\PasskeyTransformer;

class PasskeyController extends ClientApiController
{
    private const CACHE_PREFIX = 'client.account.passkeys.';

    /**
     * Returns all WebAuthn passkeys registered for the authenticated user.
     */
    public function index(ClientApiRequest $request): array
    {
        $user = $request->user();

        $passkeys = Cache::remember(
            self::CACHE_PREFIX . $user->id,
            now()->addSeconds(30),
            static fn () => $user->passkeys()
                ->orderByDesc('created_at')
                ->get([
                    'id',
                    'name',
                    'credential_id',
                    'type',
                    'last_used_at',
                    'created_at',
                ]),
        );

        return $this->fractal->collection($passkeys)
            ->transformWith(PasskeyTransformer::class)
            ->toArray();
    }

    /**
     * Generate WebAuthn registration options.
     */
    public function options(ClientApiRequest $request): array
    {
        $user = $request->user();

        // Generate a random challenge
        $challenge = bin2hex(random_bytes(32));

        // Store challenge in session for verification during registration
        session()->put('webauthn_challenge_' . $user->id, $challenge);

        // Get existing credential IDs for excludeCredentials
        $existingCredentials = $user->passkeys()
            ->pluck('credential_id')
            ->map(fn ($id) => ['id' => $id, 'type' => 'public-key'])
            ->values()
            ->toArray();

        $options = [
            'challenge' => $challenge,
            'rp' => [
                'name' => config('app.name', 'DarkOaktyl'),
                'id' => request()->getHost(),
            ],
            'user' => [
                'id' => bin2hex((string) $user->id),
                'name' => $user->email,
                'displayName' => $user->name ?? $user->username,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],    // ES256
                ['type' => 'public-key', 'alg' => -257],  // RS256
            ],
            'timeout' => 60000,
            'attestation' => 'none',
            'excludeCredentials' => $existingCredentials,
        ];

        return [
            'token' => csrf_token(),
            'options' => $options,
        ];
    }

    /**
     * Register a new WebAuthn passkey for the authenticated user.
     */
    public function store(ClientApiRequest $request): array
    {
        $validated = $this->validate($request, [
            'name' => ['required', 'string', 'max:255'],
            'credential_id' => ['required', 'string'],
            'public_key' => ['required', 'string'],
            'attestation_data' => ['nullable', 'string'],
            'transports' => ['nullable', 'json'],
            'type' => ['sometimes', 'string', 'max:50'],
        ]);

        $model = $request->user()->passkeys()->create([
            'name' => $validated['name'],
            'credential_id' => $validated['credential_id'],
            'public_key' => $validated['public_key'],
            'attestation_data' => $validated['attestation_data'] ?? null,
            'transports' => $validated['transports'] ?? null,
            'type' => $validated['type'] ?? 'public-key',
        ]);

        Cache::forget(self::CACHE_PREFIX . $request->user()->id);
        session()->forget('webauthn_challenge_' . $request->user()->id);

        Activity::event('user:passkey.create')
            ->subject($model)
            ->property('name', $model->name)
            ->log();

        return $this->fractal->item($model)
            ->transformWith(PasskeyTransformer::class)
            ->toArray();
    }

    /**
     * Remove a passkey from the authenticated user's account.
     */
    public function delete(ClientApiRequest $request): JsonResponse
    {
        $this->validate($request, ['id' => ['required', 'integer']]);

        $passkey = $request->user()->passkeys()
            ->where('id', $request->input('id'))
            ->first();

        if (!is_null($passkey)) {
            $passkey->delete();

            Activity::event('user:passkey.delete')
                ->subject($passkey)
                ->property('name', $passkey->name)
                ->log();

            Cache::forget(self::CACHE_PREFIX . $request->user()->id);
        }

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Update the name of an existing passkey.
     */
    public function update(ClientApiRequest $request): array
    {
        $this->validate($request, [
            'id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $passkey = $request->user()->passkeys()
            ->where('id', $request->input('id'))
            ->firstOrFail();

        $passkey->update([
            'name' => $request->input('name'),
        ]);

        Cache::forget(self::CACHE_PREFIX . $request->user()->id);

        Activity::event('user:passkey.update')
            ->subject($passkey)
            ->property('name', $passkey->name)
            ->log();

        return $this->fractal->item($passkey)
            ->transformWith(PasskeyTransformer::class)
            ->toArray();
    }
}