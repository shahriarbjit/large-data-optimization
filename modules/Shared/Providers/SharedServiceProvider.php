<?php

declare(strict_types=1);

namespace Modules\Shared\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Shared\Exceptions\ExceptionRenderer;
use Modules\Shared\Support\Clock;
use Modules\Shared\Support\SystemClock;

/**
 * Shared kernel bindings: clock, exception renderer and named rate limiters.
 */
final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);

        $this->app->singleton(
            ExceptionRenderer::class,
            fn ($app): ExceptionRenderer => new ExceptionRenderer($app['config']->boolean('app.debug')),
        );
    }

    public function boot(): void
    {
        // Applied to every /api/v1 route (routes/api.php).
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(
            config()->integer('api.rate_limits.api'),
        )->by('api|'.$request->ip()));

        // Applied to /api/v1/auth/* on top of "api".
        RateLimiter::for('auth', fn (Request $request): Limit => Limit::perMinute(
            config()->integer('api.rate_limits.auth'),
        )->by('auth|'.$request->ip()));
    }
}
