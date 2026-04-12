<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth2 Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Discord and Google OAuth2 authentication.
    | These credentials are used by Laravel Socialite and the OAuth
    | authentication modules in DarkOaktyl.
    |
    | To enable a provider, set the corresponding *_ENABLED flag
    | and provide the client_id and client_secret.
    |
    */

    'discord' => [
        'enabled' => env('DISCORD_OAUTH_ENABLED', false),
        'client_id' => env('DISCORD_CLIENT_ID'),
        'client_secret' => env('DISCORD_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/auth/modules/discord/authenticate',
    ],

    'google' => [
        'enabled' => env('GOOGLE_OAUTH_ENABLED', false),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/auth/modules/google/authenticate',
    ],

    /*
    |--------------------------------------------------------------------------
    | VAPID Configuration for Web Push Notifications
    |--------------------------------------------------------------------------
    */

    'vapid' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],
];
