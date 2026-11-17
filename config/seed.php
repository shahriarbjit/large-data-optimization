<?php

declare(strict_types=1);

/*
 * Development seed data (php artisan db:seed). The seeders refuse to run
 * in production.
 */
return [

    /*
     * Two accounts for local development and manual testing.
     */
    'accounts' => [
        'admin' => [
            'name' => env('SEED_ADMIN_NAME', 'Urban Nest Admin'),
            'email' => env('SEED_ADMIN_EMAIL', 'admin@urban-nest.test'),
            'phone' => env('SEED_ADMIN_PHONE', '+8801999999991'),
            'password' => env('SEED_ADMIN_PASSWORD', 'Admin@123456'),
        ],
        'user' => [
            'name' => env('SEED_USER_NAME', 'Urban Nest User'),
            'email' => env('SEED_USER_EMAIL', 'user@urban-nest.test'),
            'phone' => env('SEED_USER_PHONE', '+8801999999992'),
            'password' => env('SEED_USER_PASSWORD', 'User@1234567'),
        ],
    ],

    /*
     * Bulk fake users for load and query testing: user{n}@example.test,
     * every 10th one a guest. All share the password below. Set to 0 to skip.
     */
    'bulk_users' => (int) env('SEED_BULK_USERS', 1_000_000),

    'bulk_password' => env('SEED_BULK_PASSWORD', 'password1234'),

    // Rows per INSERT statement.
    'bulk_chunk' => 5_000,

    /*
     * Products for search practice (Catalog module). ~17 minutes and
     * ~5 GB for 10 million. Resumable: run the seeder again to continue.
     * Set to 0 to skip.
     */
    'products' => (int) env('SEED_PRODUCTS', 10_000_000),

    'products_chunk' => 2_000,

];
