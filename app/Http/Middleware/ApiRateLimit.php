<?php

namespace DarkOak\Http\Middleware;

use Illuminate\Http\Request;

/**
 * Rate Limiting Middleware for DarkOaktyl API Routes.
 *
 * Provides configurable rate limiting per route group:
 * - client-api: 60 requests/minute (standard user actions)
 * - client-api-write: 30 requests/minute (create/update/delete)
 * - client-api-sensitive: 10 requests/minute (password, 2FA, API keys)
 * - application-api: 120 requests/minute (admin actions)
 * - auth: 5 requests/minute (login/register/password-reset)
 */
class ApiRateLimit
{
    /**
     * Rate limit configurations per group.
     */
    protected static array $limits = [
        'client-api' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
            'key' => 'client-api:{user_id}',
        ],
        'client-api-write' => [
            'max_attempts' => 30,
            'decay_minutes' => 1,
            'key' => 'client-api-write:{user_id}',
        ],
        'client-api-sensitive' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
            'key' => 'client-api-sensitive:{user_id}',
        ],
        'application-api' => [
            'max_attempts' => 120,
            'decay_minutes' => 1,
            'key' => 'application-api:{user_id}',
        ],
        'auth' => [
            'max_attempts' => 5,
            'decay_minutes' => 1,
            'key' => 'auth:{ip}',
        ],
    ];

    /**
     * Get the rate limit configuration for a group.
     */
    public static function getLimit(string $group): array
    {
        return static::$limits[$group] ?? static::$limits['client-api'];
    }

    /**
     * Get the throttle middleware string for a group.
     * Usage: ->middleware('throttle:' . ApiRateLimit::for('client-api')).
     */
    public static function for(string $group): string
    {
        $config = static::getLimit($group);

        return "{$config['max_attempts']},{$config['decay_minutes']}";
    }

    /**
     * Get the rate limit key for a group and request.
     */
    public static function key(string $group, Request $request): string
    {
        $config = static::getLimit($group);
        $key = $config['key'];

        // Replace placeholders
        $key = str_replace('{user_id}', $request->user()?->id ?? $request->ip(), $key);
        $key = str_replace('{ip}', $request->ip(), $key);

        return $key;
    }

    /**
     * All defined rate limit groups.
     *
     * @return array<string, array{max_attempts: int, decay_minutes: int, key: string}>
     */
    public static function allLimits(): array
    {
        return static::$limits;
    }
}
