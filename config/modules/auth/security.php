<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lockout Configuration
    |--------------------------------------------------------------------------
    |
    | These options are DarkOaktyl specific and allow you to configure how
    | long a user should be locked out for if they input a username or
    | password incorrectly.
    |
    */
    'attempts' => env('LOGIN_ATTEMPT_LIMIT', 3),

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the enforcement level for two-factor authentication.
    |
    | Levels:
    | - NONE: 2FA is optional for all users (default)
    | - ADMIN: 2FA is required for admin users only (root_admin or admin_role_id set)
    | - ALL: 2FA is required for all users
    |
    | Legacy: 'force2fa' is still supported for backward compatibility.
    |         If force2fa is true, it maps to ALL.
    |
    */
    '2fa' => [
        // Enforcement level: 'NONE', 'ADMIN', or 'ALL'
        'enforcement' => env('2FA_ENFORCEMENT', 'NONE'),
    ],

    // Legacy support - will be mapped to 2fa.enforcement = ALL if true
    'force2fa' => env('FORCE_TWO_FACTOR', false),
];

