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
        'primary' => env('THEME_COLORS_PRIMARY', 'hsl(142 66% 45%)'),
        'primary_dark' => env('THEME_COLORS_PRIMARY_DARK', 'hsl(142 66% 37%)'),
        'primary_light' => env('THEME_COLORS_PRIMARY_LIGHT', 'hsl(142 66% 63%)'),

        'secondary' => env('THEME_COLORS_SECONDARY', 'hsl(220 14% 18%)'),
        'secondary_dark' => env('THEME_COLORS_SECONDARY_DARK', 'hsl(220 14% 16%)'),
        'secondary_light' => env('THEME_COLORS_SECONDARY_LIGHT', 'hsl(220 14% 80%)'),

        'background' => env('THEME_COLORS_BACKGROUND', 'hsl(210 24% 8%)'),
        'background_dark' => env('THEME_COLORS_BACKGROUND_DARK', 'hsl(210 24% 8%)'),
        'background_light' => env('THEME_COLORS_BACKGROUND_LIGHT', 'hsl(210 40% 96%)'),

        'headers' => env('THEME_COLORS_HEADERS', 'hsl(210 28% 12%)'),
        'headers_dark' => env('THEME_COLORS_HEADERS_DARK', 'hsl(210 28% 12%)'),
        'headers_light' => env('THEME_COLORS_HEADERS_LIGHT', 'hsl(210 32% 92%)'),

        'sidebar' => env('THEME_COLORS_SIDEBAR', 'hsl(210 24% 10%)'),
        'sidebar_dark' => env('THEME_COLORS_SIDEBAR_DARK', 'hsl(210 24% 10%)'),
        'sidebar_light' => env('THEME_COLORS_SIDEBAR_LIGHT', 'hsl(210 30% 90%)'),

        // Text colors for light / dark modes
        'text_dark' => env('THEME_COLORS_TEXT_DARK', 'hsl(0 0% 98%)'),
        'text_light' => env('THEME_COLORS_TEXT_LIGHT', 'hsl(210 24% 12%)'),

        // Explicit primary/secondary text and accent colors (editable)
        'text_primary' => env('THEME_COLORS_TEXT_PRIMARY', 'hsl(0 0% 98%)'),
        'text_primary_light' => env('THEME_COLORS_TEXT_PRIMARY_LIGHT', 'hsl(210 24% 12%)'),

        'text_secondary' => env('THEME_COLORS_TEXT_SECONDARY', 'hsl(210 16% 58%)'),
        'text_secondary_light' => env('THEME_COLORS_TEXT_SECONDARY_LIGHT', 'hsl(210 18% 34%)'),

        'accent_primary' => env('THEME_COLORS_ACCENT_PRIMARY', 'hsl(142 66% 45%)'),
        'accent_primary_light' => env('THEME_COLORS_ACCENT_PRIMARY_LIGHT', 'hsl(142 66% 45%)'),

        'accent_secondary' => env('THEME_COLORS_ACCENT_SECONDARY', 'hsl(32 94% 55%)'),
        'accent_secondary_light' => env('THEME_COLORS_ACCENT_SECONDARY_LIGHT', 'hsl(32 94% 55%)'),
        'accent_secondary_dark' => env('THEME_COLORS_ACCENT_SECONDARY_DARK', 'hsl(32 94% 55%)'),
    ],
];
