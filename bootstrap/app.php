<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
 * API-only application.
 *
 * - No web routes: there is no routes/web.php and no Blade views (see docs/decisions/0003).
 * - API routes live under the "api" prefix; routes/api.php adds the "/v1" group.
 * - Laravel's own health check stays at GET /up.
 *
 * Routing is wired here by hand. We do not run `php artisan install:api`
 * because it installs Sanctum and publishes a migration.
 */
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every response is JSON, whatever the Accept header says.
        $exceptions->shouldRenderJsonWhen(fn () => true);
    })->create();
