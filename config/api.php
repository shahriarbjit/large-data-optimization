<?php

declare(strict_types=1);

return [

    /*
     * Public API version, shown by GET /api/v1/health.
     * This is the release version of the API, not the URL version (v1).
     */
    'version' => env('API_VERSION', '0.1.0'),

    /*
     * All dates are stored and returned in UTC (ISO 8601).
     * This timezone is for human-facing output only (reports, emails, receipts).
     */
    'display_timezone' => env('API_DISPLAY_TIMEZONE', 'Asia/Dhaka'),

    /*
     * Default ISO 4217 currency for money values.
     */
    'default_currency' => env('API_DEFAULT_CURRENCY', 'BDT'),

    /*
     * List endpoints: ?page=1&per_page=20 (per_page is capped at max_per_page).
     */
    'pagination' => [
        'per_page' => 20,
        'max_per_page' => 100,
    ],

    /*
     * Named rate limiters, requests per minute per client IP.
     * Defined in Modules\Shared\Providers\SharedServiceProvider.
     */
    'rate_limits' => [
        'api' => 60,
        'auth' => 10,
    ],

];
