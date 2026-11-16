<?php

declare(strict_types=1);

namespace Modules\Orders\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Orders\Contracts\OrderServiceContract;
use Modules\Orders\Services\OrderService;

/**
 * Registered in config/modules.php. Binds this module's contracts to their
 * implementations. Routes are loaded by routes/api.php, not here.
 */
final class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderServiceContract::class, OrderService::class);
    }
}
