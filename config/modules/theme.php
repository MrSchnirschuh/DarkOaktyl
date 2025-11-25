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
        'primary' => env('THEME_COLORS_PRIMARY', '#008000'),
        'primary_dark' => env('THEME_COLORS_PRIMARY_DARK', '#008000'),
        'primary_light' => env('THEME_COLORS_PRIMARY_LIGHT', '#008000'),

    'secondary' => env('THEME_COLORS_SECONDARY', '#27272a'),
    'secondary_dark' => env('THEME_COLORS_SECONDARY_DARK', '#18181b'),
    'secondary_light' => env('THEME_COLORS_SECONDARY_LIGHT', '#e2e8f0'),

    'background' => env('THEME_COLORS_BACKGROUND', '#141414'),
    'background_dark' => env('THEME_COLORS_BACKGROUND_DARK', '#0f0f0f'),
    'background_light' => env('THEME_COLORS_BACKGROUND_LIGHT', '#f4f4f5'),

    'body' => env('THEME_COLORS_BODY', '#171717'),
    'body_dark' => env('THEME_COLORS_BODY_DARK', '#111111'),
    'body_light' => env('THEME_COLORS_BODY_LIGHT', '#fafafa'),

    'headers' => env('THEME_COLORS_HEADERS', '#171717'),
    'headers_dark' => env('THEME_COLORS_HEADERS_DARK', '#111111'),
    'headers_light' => env('THEME_COLORS_HEADERS_LIGHT', '#e5e7eb'),

    'sidebar' => env('THEME_COLORS_SIDEBAR', '#18181b'),
    'sidebar_dark' => env('THEME_COLORS_SIDEBAR_DARK', '#121212'),
    'sidebar_light' => env('THEME_COLORS_SIDEBAR_LIGHT', '#e4e4e7'),

        // Text colors for light / dark modes
        'text_dark' => env('THEME_COLORS_TEXT_DARK', '#F5F5F5'),
        'text_light' => env('THEME_COLORS_TEXT_LIGHT', '#09090B'),

        // Explicit primary/secondary text and accent colors (editable)
        'text_primary' => env('THEME_COLORS_TEXT_PRIMARY', '#F5F5F5'),
        'text_primary_light' => env('THEME_COLORS_TEXT_PRIMARY_LIGHT', '#0F172A'),

        'text_secondary' => env('THEME_COLORS_TEXT_SECONDARY', '#D4D4D8'),
        'text_secondary_light' => env('THEME_COLORS_TEXT_SECONDARY_LIGHT', '#334155'),

        'muted_text' => env('THEME_COLORS_MUTED_TEXT', '#A1A1AA'),
        'muted_text_dark' => env('THEME_COLORS_MUTED_TEXT_DARK', '#A1A1AA'),
        'muted_text_light' => env('THEME_COLORS_MUTED_TEXT_LIGHT', '#475569'),

        'accent_primary' => env('THEME_COLORS_ACCENT_PRIMARY', '#008000'),
        'accent_primary_light' => env('THEME_COLORS_ACCENT_PRIMARY_LIGHT', '#008000'),

        'accent_secondary' => env('THEME_COLORS_ACCENT_SECONDARY', '#1F2937'),
        'accent_secondary_light' => env('THEME_COLORS_ACCENT_SECONDARY_LIGHT', '#4B5563'),
        'accent_secondary_dark' => env('THEME_COLORS_ACCENT_SECONDARY_DARK', '#0F172A'),

        'button' => env('THEME_COLORS_BUTTON', '#008000'),
        'button_dark' => env('THEME_COLORS_BUTTON_DARK', '#008000'),
        'button_light' => env('THEME_COLORS_BUTTON_LIGHT', '#008000'),

        'button_text' => env('THEME_COLORS_BUTTON_TEXT', '#FFFFFF'),
        'button_text_dark' => env('THEME_COLORS_BUTTON_TEXT_DARK', '#FFFFFF'),
        'button_text_light' => env('THEME_COLORS_BUTTON_TEXT_LIGHT', '#0F0F0F'),

        'danger' => env('THEME_COLORS_DANGER', '#DC2626'),
        'danger_dark' => env('THEME_COLORS_DANGER_DARK', '#DC2626'),
        'danger_light' => env('THEME_COLORS_DANGER_LIGHT', '#EF4444'),

        'info' => env('THEME_COLORS_INFO', '#F59E0B'),
        'info_dark' => env('THEME_COLORS_INFO_DARK', '#D97706'),
        'info_light' => env('THEME_COLORS_INFO_LIGHT', '#FBBF24'),

        'warning' => env('THEME_COLORS_WARNING', '#F97316'),
        'warning_dark' => env('THEME_COLORS_WARNING_DARK', '#EA580C'),
        'warning_light' => env('THEME_COLORS_WARNING_LIGHT', '#FB923C'),

        'experimental' => env('THEME_COLORS_EXPERIMENTAL', '#FACC15'),
        'experimental_dark' => env('THEME_COLORS_EXPERIMENTAL_DARK', '#FACC15'),
        'experimental_light' => env('THEME_COLORS_EXPERIMENTAL_LIGHT', '#FCD34D'),

        'success' => env('THEME_COLORS_SUCCESS', '#16A34A'),
        'success_dark' => env('THEME_COLORS_SUCCESS_DARK', '#15803D'),
        'success_light' => env('THEME_COLORS_SUCCESS_LIGHT', '#22C55E'),

        // Default logos wired into the theme colors map so React consumers get them automatically.
        'logo_panel' => env('THEME_LOGO_PANEL', '/assets/brand/DarkOak_CL.svg'),
        'logo_panel_light' => env('THEME_LOGO_PANEL_LIGHT', env('THEME_LOGO_PANEL', '/assets/brand/DarkOak_CL.svg')),
        'logo_panel_dark' => env('THEME_LOGO_PANEL_DARK', env('THEME_LOGO_PANEL', '/assets/brand/DarkOak_CL.svg')),

        'logo_login' => env('THEME_LOGO_LOGIN', '/assets/brand/DarkOak_CL.svg'),
        'logo_login_light' => env('THEME_LOGO_LOGIN_LIGHT', env('THEME_LOGO_LOGIN', '/assets/brand/DarkOak_CL.svg')),
        'logo_login_dark' => env('THEME_LOGO_LOGIN_DARK', env('THEME_LOGO_LOGIN', '/assets/brand/DarkOak_CL.svg')),
    ],
];
