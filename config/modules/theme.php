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
        'primary' => env('THEME_COLORS_PRIMARY', '#16A34A'),
        'primary_dark' => env('THEME_COLORS_PRIMARY_DARK', '#16A34A'),
        'primary_light' => env('THEME_COLORS_PRIMARY_LIGHT', '#22C55E'),

    'secondary' => env('THEME_COLORS_SECONDARY', '#27272a'),
    'secondary_dark' => env('THEME_COLORS_SECONDARY_DARK', '#18181b'),
    'secondary_light' => env('THEME_COLORS_SECONDARY_LIGHT', '#E6E2DC'),

    'background' => env('THEME_COLORS_BACKGROUND', '#141414'),
    'background_dark' => env('THEME_COLORS_BACKGROUND_DARK', '#0f0f0f'),
    'background_light' => env('THEME_COLORS_BACKGROUND_LIGHT', '#F8F6F3'),

    'body' => env('THEME_COLORS_BODY', '#171717'),
    'body_dark' => env('THEME_COLORS_BODY_DARK', '#111111'),
    'body_light' => env('THEME_COLORS_BODY_LIGHT', '#FDFCFA'),

    'headers' => env('THEME_COLORS_HEADERS', '#171717'),
    'headers_dark' => env('THEME_COLORS_HEADERS_DARK', '#111111'),
    'headers_light' => env('THEME_COLORS_HEADERS_LIGHT', '#EFEDE9'),

    'sidebar' => env('THEME_COLORS_SIDEBAR', '#18181b'),
    'sidebar_dark' => env('THEME_COLORS_SIDEBAR_DARK', '#121212'),
    'sidebar_light' => env('THEME_COLORS_SIDEBAR_LIGHT', '#F4F1EC'),

        // Text colors for light / dark modes
        'text_dark' => env('THEME_COLORS_TEXT_DARK', '#F5F5F5'),
        'text_light' => env('THEME_COLORS_TEXT_LIGHT', '#1C1917'),

        // Explicit primary/secondary text and accent colors (editable)
        'text_primary' => env('THEME_COLORS_TEXT_PRIMARY', '#F5F5F5'),
        'text_primary_light' => env('THEME_COLORS_TEXT_PRIMARY_LIGHT', '#1C1917'),

        'text_secondary' => env('THEME_COLORS_TEXT_SECONDARY', '#D4D4D8'),
        'text_secondary_light' => env('THEME_COLORS_TEXT_SECONDARY_LIGHT', '#44403C'),

        'muted_text' => env('THEME_COLORS_MUTED_TEXT', '#A1A1AA'),
        'muted_text_dark' => env('THEME_COLORS_MUTED_TEXT_DARK', '#A1A1AA'),
        'muted_text_light' => env('THEME_COLORS_MUTED_TEXT_LIGHT', '#78716C'),

        'accent_primary' => env('THEME_COLORS_ACCENT_PRIMARY', '#16A34A'),
        'accent_primary_light' => env('THEME_COLORS_ACCENT_PRIMARY_LIGHT', '#22C55E'),

        'accent_secondary' => env('THEME_COLORS_ACCENT_SECONDARY', '#1F2937'),
        'accent_secondary_light' => env('THEME_COLORS_ACCENT_SECONDARY_LIGHT', '#57534E'),
        'accent_secondary_dark' => env('THEME_COLORS_ACCENT_SECONDARY_DARK', '#0F172A'),

        'button' => env('THEME_COLORS_BUTTON', '#16A34A'),
        'button_dark' => env('THEME_COLORS_BUTTON_DARK', '#16A34A'),
        'button_light' => env('THEME_COLORS_BUTTON_LIGHT', '#22C55E'),

        'button_text' => env('THEME_COLORS_BUTTON_TEXT', '#FFFFFF'),
        'button_text_dark' => env('THEME_COLORS_BUTTON_TEXT_DARK', '#FFFFFF'),
        'button_text_light' => env('THEME_COLORS_BUTTON_TEXT_LIGHT', '#FFFFFF'),
    ],
];
