<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Server Groups Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the server grouping feature.
    |
    */

    // Maximum number of groups a user can create
    'max_groups_per_user' => env('SERVER_GROUPS_MAX_PER_USER', 10),

    // Maximum servers per group
    'max_servers_per_group' => env('SERVER_GROUPS_MAX_SERVERS', 50),

    // Available colors for group labels
    'available_colors' => [
        '#3b82f6', // Blue
        '#ef4444', // Red
        '#10b981', // Green
        '#f59e0b', // Yellow
        '#8b5cf6', // Purple
        '#ec4899', // Pink
        '#06b6d4', // Cyan
        '#f97316', // Orange
    ],

    // Available icons for groups
    'available_icons' => [
        'folder',
        'server',
        'database',
        'cloud',
        'star',
        'shield',
        'globe',
        'cpu',
    ],
];
