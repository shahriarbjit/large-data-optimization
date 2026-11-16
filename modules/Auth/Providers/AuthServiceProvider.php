<?php

declare(strict_types=1);

namespace Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Auth\Contracts\AuthServiceContract;
use Modules\Auth\Services\AuthService;

/**
 * Registered in config/modules.php. Binds this module's contracts to their
 * implementations and loads the module's migrations. Routes are loaded by
 * routes/api.php, not here.
 */
final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthServiceContract::class, AuthService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
