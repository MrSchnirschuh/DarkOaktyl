<?php

return [
    /*
     * Toggle access to the templated email management module.
     */
    'enabled' => env('EMAILS_ENABLED', true),

    /*
     * The UUID of the theme that should be considered the default when rendering
     * emails. This value is maintained automatically when a new default theme is
     * chosen through the admin UI.
     */
    'default_theme' => env('EMAILS_DEFAULT_THEME'),

    /*
     * Default styling values applied when the database does not yet contain a theme.
     */
    'defaults' => [
        'name' => 'DarkOaktyl Default',
        'primary_color' => env('THEME_COLORS_PRIMARY', '#008000'),
        'secondary_color' => env('THEME_COLORS_SECONDARY', '#27272a'),
        'accent_color' => env('THEME_COLORS_ACCENT_PRIMARY', '#008000'),
        'background_color' => env('THEME_COLORS_BACKGROUND', '#141414'),
        'body_color' => env('THEME_COLORS_BODY', '#171717'),
        'text_color' => env('THEME_COLORS_TEXT_PRIMARY', '#f5f5f5'),
        'muted_text_color' => env('THEME_COLORS_MUTED_TEXT', '#a1a1aa'),
        'button_color' => env('THEME_COLORS_BUTTON', '#008000'),
        'button_text_color' => env('THEME_COLORS_BUTTON_TEXT', '#ffffff'),
        'footer_text' => '© ' . date('Y') . ' ' . config('app.name', 'DarkOaktyl') . '. All rights reserved.',
        'variant_mode' => 'dual',
        'light_palette' => [
            'primary' => env('THEME_COLORS_PRIMARY_LIGHT', '#008000'),
            'secondary' => env('THEME_COLORS_SECONDARY_LIGHT', '#3f3f46'),
            'accent' => env('THEME_COLORS_ACCENT_PRIMARY_LIGHT', '#008000'),
            'background' => env('THEME_COLORS_BACKGROUND_LIGHT', '#f4f4f5'),
            'body' => env('THEME_COLORS_BODY_LIGHT', '#fafafa'),
            'text' => env('THEME_COLORS_TEXT_PRIMARY_LIGHT', '#09090b'),
            'muted' => env('THEME_COLORS_MUTED_TEXT_LIGHT', '#6b7280'),
            'button' => env('THEME_COLORS_BUTTON_LIGHT', '#008000'),
            'button_text' => env('THEME_COLORS_BUTTON_TEXT_LIGHT', '#0f0f0f'),
        ],
    ],
];
