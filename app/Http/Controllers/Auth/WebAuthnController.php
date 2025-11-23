<?php

namespace DarkOak\Http\Controllers\Auth;

use Carbon\CarbonImmutable;
use DarkOak\Exceptions\DisplayException;
use DarkOak\Facades\Activity;
use DarkOak\Models\User;
use DarkOak\Services\Passkeys\PasskeyService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebAuthnController extends AbstractLoginController
{
    public function __construct(private PasskeyService $passkeyService)
    {
        parent::__construct();
    }

    public function options(Request $request): JsonResponse
    {
        $this->ensurePasskeysEnabled();

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        $this->validate($request, [
            'user' => ['required', 'string'],
        ]);

        try {
            $identifier = $request->input('user');
            /** @var User $user */
            $user = User::query()->where($this->getField($identifier), $identifier)->firstOrFail();
        } catch (ModelNotFoundException) {
            $this->sendFailedLoginResponse($request);
        }

        if (!$user->canLoginWithPasskey()) {
            $this->sendFailedLoginResponse($request, $user);
        }

        try {
            $options = $this->passkeyService->createLoginOptions($user);
        } catch (DisplayException $exception) {
            throw new DisplayException($exception->getMessage());
        }

        return new JsonResponse(['data' => $options]);
    }

    public function verify(Request $request): JsonResponse
    {
        $this->ensurePasskeysEnabled();

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        $this->validate($request, [
            'token' => ['required', 'string'],
            'credential' => ['required', 'array'],
        ]);

        try {
            $passkey = $this->passkeyService->verifyLogin(
                $request->input('token'),
                $request->input('credential')
            );
        } catch (DisplayException $exception) {
            $this->sendFailedLoginResponse($request, null, $exception->getMessage());
        }

        $user = $passkey->user;

        if (!$user->canLoginWithPasskey()) {
            $this->sendFailedLoginResponse($request, $user);
        }

        $this->clearLoginAttempts($request);

        Activity::event('auth:passkey.login')->subject($user)->withRequestMetadata()->log();

        if (!$user->use_totp) {
            return $this->sendLoginResponse($user, $request);
        }

        $token = Str::random(64);

        Activity::event('auth:checkpoint')->withRequestMetadata()->subject($user)->log();

        $request->session()->put('auth_confirmation_token', [
            'user_id' => $user->id,
            'token_value' => $token,
            'expires_at' => CarbonImmutable::now()->addMinutes(5),
        ]);

        return new JsonResponse([
            'data' => [
                'complete' => false,
                'confirmation_token' => $token,
            ],
        ]);
    }

    private function ensurePasskeysEnabled(): void
    {
        if (!boolval(config('modules.auth.passkeys.enabled'))) {
            throw new DisplayException('Passkey authentication is not enabled.');
        }
    }
}
