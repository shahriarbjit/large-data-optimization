<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\ProductController;

/*
 * Catalog routes. Loaded by routes/api.php inside the /api/v1 group.
 * Public and read-only. Search comes later (Elasticsearch).
 */
Route::prefix('products')
    ->name('products.')
    ->group(function (): void {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('{product}', [ProductController::class, 'show'])->whereNumber('product')->name('show');
    });
