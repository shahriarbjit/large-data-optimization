<?php

declare(strict_types=1);

namespace Modules\Catalog\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Catalog\Contracts\ProductCatalogContract;
use Modules\Catalog\Services\ProductCatalogService;

/**
 * Registered in config/modules.php. Catalog owns the products table.
 * Routes are loaded by routes/api.php, not here.
 */
final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductCatalogContract::class, ProductCatalogService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
