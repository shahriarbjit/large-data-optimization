<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Shared\Http\Controllers\HealthController;

/*
 * All API routes are prefixed with "/api" (see bootstrap/app.php).
 * Versioned routes live inside the "/v1" group below, with the "api"
 * rate limiter (60 requests per minute per IP, config/api.php).
 */
Route::prefix('v1')
    ->name('v1.')
    ->middleware('throttle:api')
    ->group(function (): void {
        Route::get('health', HealthController::class)->name('health');
    });
