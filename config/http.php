<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Rate Limits
    |--------------------------------------------------------------------------
    |
    | Defines the rate limit for the number of requests per minute that can be
    | executed against both the client and internal (application) APIs over the
    | defined period (by default, 1 minute).
    |
    */
    'rate_limit' => [
        'client_period' => 1,
        'client' => env('APP_API_CLIENT_RATELIMIT', 720),

        'application_period' => 1,
        'application' => env('APP_API_APPLICATION_RATELIMIT', 240),

        // Daemon API rate limits - protects Wings daemon communication endpoints
        'daemon_period' => 1,
        'daemon' => env('APP_API_DAEMON_RATELIMIT', 120),

        // Stricter limits for specific high-impact daemon endpoints
        'daemon_command_period' => 1,
        'daemon_command' => env('APP_API_DAEMON_COMMAND_RATELIMIT', 30),

        'daemon_sftp_period' => 1,
        'daemon_sftp' => env('APP_API_DAEMON_SFTP_RATELIMIT', 60),
    ],
];
