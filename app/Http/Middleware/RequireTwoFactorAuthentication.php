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
    protected string $redirectRoute = '/account';

    /**
     * Get the current 2FA enforcement level from config.
     * Supports new '2fa.enforcement' format with fallback to legacy 'force2fa'.
     */
    protected function getEnforcementLevel(): string
    {
        $config = config('modules.auth.security');

        // New format: 2fa.enforcement
        if (isset($config['2fa']['enforcement'])) {
            $level = strtoupper($config['2fa']['enforcement']);
            if (in_array($level, ['NONE', 'ADMIN', 'ALL'])) {
                return $level;
            }
        }

        // Legacy fallback: force2fa boolean
        if (!empty($config['force2fa'])) {
            return 'ALL';
        }

        return 'NONE';
    }

    /**
     * Check if user is an admin based on root_admin flag or admin_role_id.
     */
    protected function isAdmin(User $user): bool
    {
        return $user->root_admin || !is_null($user->admin_role_id);
    }

    /**
     * Check the user state on the incoming request to determine if they should be allowed to
     * proceed or not. This checks if the Panel is configured to require 2FA on an account in
     * order to perform actions. If so, we check the level at which it is required (all users
     * or just admins) and then check if the user has enabled it for their account.
     *
     * @throws \DarkOak\Exceptions\Http\TwoFactorAuthRequiredException
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        $user = $request->user();
        $uri = rtrim($request->getRequestUri(), '/') . '/';
        $current = $request->route()?->getName();

        // Must be logged in
        if (!$user instanceof User) {
            return $next($request);
        }

        // Allow access to auth and account routes regardless of 2FA status
        if (Str::startsWith($uri, ['/auth/', '/account/']) || ($current !== null && Str::startsWith($current, ['auth.', 'account.']))) {
            return $next($request);
        }

        // Get enforcement level
        $enforcementLevel = $this->getEnforcementLevel();

        // NONE = No enforcement required
        if ($enforcementLevel === 'NONE') {
            return $next($request);
        }

        // User already has 2FA enabled - allow through
        if ($user->use_totp) {
            return $next($request);
        }

        // ADMIN = Only require 2FA for admin users
        if ($enforcementLevel === 'ADMIN' && !$this->isAdmin($user)) {
            return $next($request);
        }

        // At this point, 2FA is required but user doesn't have it enabled
        // For API calls return an exception which gets rendered nicely in the API response.
        if ($request->isJson() || Str::startsWith($uri, '/api/')) {
            throw new TwoFactorAuthRequiredException();
        }

        return new \Illuminate\Http\RedirectResponse($this->redirectRoute);
    }
}

