<?php

namespace DarkOak\Http\Controllers\Api\Client;

use DarkOak\Exceptions\DisplayException;
use DarkOak\Http\Requests\Api\Client\Account\StorePasskeyRequest;
use DarkOak\Http\Requests\Api\Client\Account\UpdatePasskeyMethodRequest;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;
use DarkOak\Models\User;
use DarkOak\Models\UserPasskey;
use DarkOak\Services\Passkeys\PasskeyService;
use DarkOak\Transformers\Api\Client\UserPasskeyTransformer;
use Illuminate\Http\JsonResponse;

class PasskeyController extends ClientApiController
{
    public function __construct(private PasskeyService $passkeyService)
    {
        parent::__construct();
    }

    public function index(ClientApiRequest $request): array
    {
        $this->ensurePasskeysEnabled();

        $passkeys = $request->user()->passkeys()->orderByDesc('created_at')->get();

        return $this->fractal->collection($passkeys)
            ->transformWith(UserPasskeyTransformer::class)
            ->toArray();
    }

    public function options(ClientApiRequest $request): JsonResponse
    {
        $this->ensurePasskeysEnabled();

        $data = $this->passkeyService->createRegistrationOptions($request->user());

        return new JsonResponse(['data' => $data]);
    }

    public function store(StorePasskeyRequest $request): array
    {
        $this->ensurePasskeysEnabled();

        $passkey = $this->passkeyService->finalizeRegistration(
            $request->user(),
            $request->input('token'),
            $request->input('credential'),
            $request->input('name')
        );

        return $this->fractal->item($passkey)
            ->transformWith(UserPasskeyTransformer::class)
            ->toArray();
    }

    public function delete(ClientApiRequest $request, UserPasskey $passkey): JsonResponse
    {
        $this->ensurePasskeysEnabled();

        if ($passkey->user_id !== $request->user()->id) {
            abort(404);
        }

        $passkey->delete();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    public function updateMethod(UpdatePasskeyMethodRequest $request): JsonResponse
    {
        $this->ensurePasskeysEnabled();

        $method = $request->input('method');
        $user = $request->user();

        if ($method === User::AUTH_LOGIN_METHOD_PASSKEY && $user->passkeys()->count() === 0) {
            throw new DisplayException('Add at least one passkey before enabling passkey-only logins.');
        }

        $user->update(['auth_login_method' => $method]);

        return new JsonResponse([
            'data' => [
                'method' => $method,
            ],
        ]);
    }

    private function ensurePasskeysEnabled(): void
    {
        if (!boolval(config('modules.auth.passkeys.enabled'))) {
            throw new DisplayException('Passkey support is not enabled.');
        }
    }
}
