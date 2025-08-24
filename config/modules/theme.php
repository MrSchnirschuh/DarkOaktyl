<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Theme Configuration
    |--------------------------------------------------------------------------
    |
    | These settings allow you to set custom hex values for the Panel's theme.
    | This can be configured in the admin pages, and can also be edited here
    | for ease of use.
    |
    */
    'colors' => [
        'primary' => env('THEME_COLORS_PRIMARY', '#16a34a'),
        'secondary' => env('THEME_COLORS_SECONDARY', '#27272a'),

        'background' => env('THEME_COLORS_BACKGROUND', '#141414'),
        'background_light' => env('THEME_COLORS_BACKGROUND_LIGHT', '#ffffff'),
        'background_dark' => env('THEME_COLORS_BACKGROUND_DARK', '#141414'),
        'headers' => env('THEME_COLORS_HEADERS', '#171717'),
        'sidebar' => env('THEME_COLORS_SIDEBAR', '#18181b'),

    // Text colors for light / dark modes
    'text_light' => env('THEME_COLORS_TEXT_LIGHT', '#111827'),
    'text_dark' => env('THEME_COLORS_TEXT_DARK', '#ffffff'),

    // Explicit primary/secondary text and accent colors (editable)
    'text_primary' => env('THEME_COLORS_TEXT_PRIMARY', '#ffffff'),
    'text_secondary' => env('THEME_COLORS_TEXT_SECONDARY', '#9ca3af'),
    'accent_primary' => env('THEME_COLORS_ACCENT_PRIMARY', '#16a34a'),
    ],
];
