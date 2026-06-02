<?php

namespace DarkOak\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use DarkOak\Services\Users\UserUpdateService;
use DarkOak\Transformers\Api\Client\AccountTransformer;
use DarkOak\Http\Requests\Api\Client\Account\SetupUserRequest;
use DarkOak\Http\Requests\Api\Client\Account\UpdateEmailRequest;
use DarkOak\Http\Requests\Api\Client\Account\UpdatePasswordRequest;
use DarkOak\Http\Requests\Api\Client\Account\UpdateAppearanceRequest;

class AccountController extends ClientApiController
{
    /**
     * AccountController constructor.
     */
    public function __construct(private AuthManager $manager, private UserUpdateService $updateService)
    {
        parent::__construct();
    }

    public function index(Request $request): array
    {
        return $this->fractal->item($request->user())
            ->transformWith(AccountTransformer::class)
            ->toArray();
    }

    /**
     * Update the authenticated user's email address.
     */
    public function updateEmail(UpdateEmailRequest $request): JsonResponse
    {
        $original = $request->user()->email;
        $this->updateService->handle($request->user(), $request->validated());

        if ($original !== $request->input('email')) {
            Activity::event('user:account.email-changed')
                ->property(['old' => $original, 'new' => $request->input('email')])
                ->log();
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Update the authenticated user's password. All existing sessions will be logged
     * out immediately.
     *
     * @throws \Throwable
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $this->updateService->handle($request->user(), $request->validated());

        $guard = $this->manager->guard();
        // If you do not update the user in the session you'll end up working with a
        // cached copy of the user that does not include the updated password. Do this
        // to correctly store the new user details in the guard and allow the logout
        // other devices functionality to work.
        $guard->setUser($user);

        // This method doesn't exist in the stateless Sanctum world.
        if (method_exists($guard, 'logoutOtherDevices')) {
            $guard->logoutOtherDevices($request->input('password'));
        }

        Activity::event('user:account.password-changed')->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Return the authenticated user's appearance preferences.
     */
    public function appearance(Request $request): JsonResponse
    {
        $user = $request->user();

        return new JsonResponse([
            'appearance_mode' => $user->appearance_mode ?? 'system',
            'appearance_last_mode' => $user->appearance_last_mode ?? 'dark',
        ]);
    }

    public function updateAppearance(UpdateAppearanceRequest $request): JsonResponse
    {
        $this->updateService->handle($request->user(), [
            'appearance_mode' => $request->input('mode'),
            'appearance_last_mode' => $request->input('last_mode'),
        ]);

        Activity::event('user:account.appearance-updated')
            ->property('mode', $request->input('mode'))
            ->property('last_mode', $request->input('last_mode'))
            ->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Set up an account when registered with OAuth2.
     */
    public function setup(SetupUserRequest $request): JsonResponse
    {
        $user = $this->updateService->handle($request->user(), $request->validated());

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Return the current auth login method for the authenticated user.
     */
    public function authLoginMethod(Request $request): JsonResponse
    {
        $user = $request->user();

        return new JsonResponse([
            'auth_login_method' => $user->auth_login_method ?? 'password',
        ]);
    }

    /**
     * Update the auth login method for the authenticated user.
     */
    public function updateAuthLoginMethod(Request $request): JsonResponse
    {
        $this->validate($request, [
            'method' => ['required', 'string', 'in:password,passkey'],
        ]);

        $user = $request->user();
        $user->auth_login_method = $request->input('method');
        $user->save();

        Activity::event('user:account.login-method-changed')
            ->property(['old' => $user->getOriginal('auth_login_method'), 'new' => $request->input('method')])
            ->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}

