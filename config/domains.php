<?php

return [
    'sync' => [
        'enabled' => env('DOMAIN_SYNC_ENABLED', false),
        'endpoint' => env('DOMAIN_SYNC_ENDPOINT'),
        'token' => env('DOMAIN_SYNC_TOKEN'),
        'timeout' => env('DOMAIN_SYNC_TIMEOUT', 10),
    ],
];
