<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Shared\Http\Controllers\HealthController;
use Modules\Shared\Providers\ModulesServiceProvider;

/*
 * All API routes are prefixed with "/api" (see bootstrap/app.php).
 * Versioned routes live inside the "/v1" group below, with the "api"
 * rate limiter (60 requests per minute per IP, config/api.php).
 *
 * Each enabled module (config/modules.php) adds its routes from
 * modules/{Module}/routes/api_v1.php.
 */
Route::prefix('v1')
    ->name('v1.')
    ->middleware('throttle:api')
    ->group(function (): void {
        Route::get('health', HealthController::class)->name('health');

        foreach (array_keys(ModulesServiceProvider::enabledModules()) as $module) {
            require ModulesServiceProvider::routeFile($module);
        }
    });
