<?php

declare(strict_types=1);

namespace Modules\Auth\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Contracts\AuthServiceContract;
use Modules\Auth\Contracts\UserDirectoryContract;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\UserPolicy;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\UserDirectoryService;

/**
 * Registered in config/modules.php. Binds this module's contracts to their
 * implementations, registers its policies and loads its migrations.
 * Routes are loaded by routes/api.php, not here.
 */
final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthServiceContract::class, AuthService::class);
        $this->app->bind(UserDirectoryContract::class, UserDirectoryService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Policies in modules are not auto-discovered. A named ability keeps
        // controllers free of model classes (architecture rule).
        Gate::policy(User::class, UserPolicy::class);
        Gate::define('users.viewAny', [UserPolicy::class, 'viewAny']);
    }
}
