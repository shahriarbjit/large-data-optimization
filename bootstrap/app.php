<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Modules\Shared\Exceptions\ApiException;
use Modules\Shared\Exceptions\ExceptionRenderer;
use Modules\Shared\Http\Middleware\AssignRequestId;
use Modules\Shared\Http\Middleware\ForceJsonResponse;
use Modules\Shared\Http\Middleware\IdempotencyKeyStub;

/*
 * API-only application.
 *
 * - No web routes: there is no routes/web.php and no Blade views
 *   (docs/decisions/0003-api-only-no-web-layer.md).
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
        // First in the global stack, so they also cover 404/405 and
        // errors raised by Laravel's own global middleware.
        $middleware->prepend([
            AssignRequestId::class,
            ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'idempotency' => IdempotencyKeyStub::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every error uses the standard error body (docs/api-conventions.md).
        $exceptions->render(
            fn (Throwable $e, Request $request) => app(ExceptionRenderer::class)->render($e, $request),
        );

        // Fallback if a renderer ever returns null: still JSON, never HTML.
        $exceptions->shouldRenderJsonWhen(fn () => true);

        // Expected API errors (4xx, and 501 from unfinished endpoints) are
        // answers, not incidents. Unexpected 5xx errors are still logged.
        $exceptions->dontReportWhen(
            fn (Throwable $e) => $e instanceof ApiException && ($e->status() < 500 || $e->status() === 501),
        );
    })->create();
