<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\OrderController;

/*
 * Orders routes. Loaded by routes/api.php inside the /api/v1 group, so the
 * full path is /api/v1/orders/...
 *
 * {order} is the integer order id (docs/decisions/0004-integer-ids.md).
 * A non-numeric id does not match and returns 404.
 */
Route::prefix('orders')
    ->name('orders.')
    ->group(function (): void {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::post('/', [OrderController::class, 'store'])->name('store')->middleware('idempotency');
        Route::get('{order}', [OrderController::class, 'show'])->name('show')->whereNumber('order');
        Route::post('{order}/cancel', [OrderController::class, 'cancel'])->name('cancel')->whereNumber('order');
    });
