<?php

return [
    'defaults' => [
        [
            'slug' => 'terms-of-service',
            'title' => 'Terms of Service',
            'content' => 'Please provide your Terms of Service.',
            'is_published' => true,
        ],
        [
            'slug' => 'legal-notice',
            'title' => 'Legal Notice',
            'content' => 'Please provide your legal notice / imprint.',
            'is_published' => true,
        ],
    ],
    'legacy_slugs' => [
        'agb' => 'terms-of-service',
        'impressum' => 'legal-notice',
    ],
];
