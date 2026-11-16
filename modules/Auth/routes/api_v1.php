<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

/*
 * Auth routes. Loaded by routes/api.php inside the /api/v1 group, so the
 * full path is /api/v1/auth/... All of them use the stricter "auth" rate
 * limiter (10 requests per minute per IP) on top of "api".
 */
Route::prefix('auth')
    ->name('auth.')
    ->middleware('throttle:auth')
    ->group(function (): void {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');

        // TODO(auth): add the authentication middleware to these two routes.
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
    });
