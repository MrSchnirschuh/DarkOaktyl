<?php

return [
    /*
     * Enable or disable the biling module.
     */
    'enabled' => env('BILLING_ENABLED', false),

    /*
     * Configure the publishable & secret API key for Stripe.
     */
    'keys' => [
        'publishable' => env('BILLING_PUBLISHABLE_KEY', ''),
        'secret' => env('BILLING_SECRET_KEY', ''),
    ],

    /*
     * Choose whether to add PayPal integration to the Panel.
     */
    'paypal' => env('BILLING_PAYPAL', false),

    /*
     * Choose whether to add Link integration to the Panel.
     */
    'link' => env('BILLING_LINK', false),

    /*
     * Set a currency code and symbol to use for billing.
     */
    'currency' => [
        'symbol' => '$',
        'code' => 'usd',
    ],

    /*
     * Control what storefront experience is shown to end-users.
     * Available modes: products, builder, hybrid. Hybrid exposes both
     * flows and only surfaces a "no products" error when neither has data.
     */
    'storefront' => [
        'mode' => env('BILLING_STOREFRONT_MODE', 'products'),
    ],

    /*
     * Configuration for the resource-based server builder experience.
     */
    'builder' => [
        'resource_map' => [
            'memory' => ['attribute' => 'memory', 'unit' => 'mb'],
            'memory_mb' => ['attribute' => 'memory', 'unit' => 'mb'],
            'disk' => ['attribute' => 'disk', 'unit' => 'mb'],
            'disk_mb' => ['attribute' => 'disk', 'unit' => 'mb'],
            'cpu' => ['attribute' => 'cpu', 'unit' => 'percent'],
            'cpu_percent' => ['attribute' => 'cpu', 'unit' => 'percent'],
            'backups' => ['attribute' => 'backup_limit', 'unit' => 'count'],
            'databases' => ['attribute' => 'database_limit', 'unit' => 'count'],
            'allocations' => ['attribute' => 'allocation_limit', 'unit' => 'count'],
        ],
        'defaults' => [
            'memory' => 1024,
            'disk' => 10240,
            'cpu' => 100,
            'backup_limit' => 0,
            'database_limit' => 0,
            'allocation_limit' => 1,
            'swap' => 0,
            'io' => 500,
            'subuser_limit' => 3,
        ],
    ],
];
