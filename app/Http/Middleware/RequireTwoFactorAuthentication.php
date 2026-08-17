<?php

namespace DarkOak\Http\Middleware;

use DarkOak\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use DarkOak\Exceptions\Http\TwoFactorAuthRequiredException;

class RequireTwoFactorAuthentication
{
    public const LEVEL_NONE = 0;
    public const LEVEL_ADMIN = 1;
    public const LEVEL_ALL = 2;

    /**
     * The route to redirect a user to enable 2FA.
     */
    protected string $redirectRoute = '/account/security';

    /**
     * Check the user state on the incoming request to determine if they should be allowed to
     * proceed or not. This checks if the Panel is configured to require 2FA on an account in
     * order to perform actions. If so, we check the level at which it is required (all users
     * or just admins) and then check if the user has enabled it for their account.
     *
     * @throws TwoFactorAuthRequiredException
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        $user = $request->user();
        $uri = rtrim($request->getRequestUri(), '/') . '/';
        $route = $request->route();
        $current = $route ? $route->getName() : null;

        // Must be logged in
        if (!$user instanceof User) {
            return $next($request);
        }

        if (Str::startsWith($uri, ['/auth/', '/account/']) || Str::startsWith($current, ['auth.', 'account.'])) {
            return $next($request);
        }

        $enforcement = config('modules.auth.security.2fa.enforcement');
        $legacyForce = config('modules.auth.security.force2fa');

        // Determine required level: legacy boolean maps to ALL.
        $level = match (strtoupper((string) $enforcement)) {
            'ADMIN' => self::LEVEL_ADMIN,
            'ALL' => self::LEVEL_ALL,
            'NONE' => self::LEVEL_NONE,
            default => self::LEVEL_NONE,
        };

        // Legacy boolean force2fa takes precedence: true maps to requiring ALL users.
        if ($legacyForce) {
            $level = self::LEVEL_ALL;
        }

        if ($level === self::LEVEL_NONE) {
            return $next($request);
        }

        // Already using TOTP or authenticated with a passkey satisfies MFA.
        $hasPasskeySession = $request->hasSession() && $request->session()->get('auth_passkey', false);
        if ($user->use_totp || $hasPasskeySession) {
            return $next($request);
        }

        // Admin-level enforcement only applies to admins.
        if ($level === self::LEVEL_ADMIN && !$this->isAdmin($user)) {
            return $next($request);
        }

        // For API calls return an exception which gets rendered nicely in the API response.
        if ($request->isJson() || Str::startsWith($uri, '/api/')) {
            throw new TwoFactorAuthRequiredException();
        }

        return redirect()->to($this->redirectRoute);
    }

    private function isAdmin(User $user): bool
    {
        return $user->root_admin || $user->admin_role_id !== null;
    }
}
