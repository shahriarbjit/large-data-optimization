<?php

declare(strict_types=1);

/*
 * ADR 0009: the Docker MySQL is the only development database, and its
 * connection details are the defaults in config/database.php.
 */
it('ships no database settings in .env.example', function (): void {
    $lines = file(base_path('.env.example'), FILE_IGNORE_NEW_LINES) ?: [];

    expect(array_values(preg_grep('/^\s*DB_/', $lines) ?: []))->toBe([]);
});

it('defaults to the Docker MySQL as seen from the host', function (): void {
    $config = file_get_contents(config_path('database.php')) ?: '';

    expect($config)
        ->toContain("env('DB_CONNECTION', 'mysql')")
        ->toContain("env('DB_PORT', '3307')")
        ->toContain("env('DB_DATABASE', 'urban_nest_api')")
        ->toContain("env('DB_PASSWORD', 'secret')");
});
