<?php

declare(strict_types=1);

/*
 * Cross-Origin Resource Sharing, applied by Laravel's HandleCors middleware.
 * Allowed origins come from CORS_ALLOWED_ORIGINS (comma-separated).
 */
return [

    'paths' => ['api/*', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173')),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    // Headers the browser is allowed to read from responses.
    'exposed_headers' => [
        'X-Request-Id',
        'Retry-After',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
    ],

    'max_age' => 0,

    // Token auth via the Authorization header; no cookies.
    'supports_credentials' => false,

];
