<?php

return [
    'enabled' => env('AUTH_PASSKEYS_ENABLED', false),
    'challenge_ttl' => env('AUTH_PASSKEY_CHALLENGE_TTL', 300),
    'max_per_user' => env('AUTH_PASSKEY_MAX_PER_USER', 5),
    'timeout' => env('AUTH_PASSKEY_TIMEOUT', 60000),
    'relying_party' => [
        'name' => env('AUTH_PASSKEY_RP_NAME', config('app.name', 'DarkOaktyl')),
        'id' => env('AUTH_PASSKEY_RP_ID', parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost'),
    ],
];
