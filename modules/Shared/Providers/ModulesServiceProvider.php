<?php

declare(strict_types=1);

namespace Modules\Shared\Providers;

use Illuminate\Support\ServiceProvider;
use UnexpectedValueException;

/**
 * Loads the shared kernel and every module listed in config/modules.php.
 *
 * Nothing is discovered automatically: a module is loaded only when it is
 * listed in config/modules.php. Its routes are loaded by routes/api.php.
 */
final class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(SharedServiceProvider::class);

        foreach (self::enabledModules() as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * Enabled modules from config/modules.php, module name => provider class.
     *
     * @return array<string, class-string<ServiceProvider>>
     */
    public static function enabledModules(): array
    {
        $enabled = config('modules.enabled', []);

        if (! is_array($enabled)) {
            throw new UnexpectedValueException('config/modules.php "enabled" must be an array.');
        }

        $modules = [];

        foreach ($enabled as $name => $provider) {
            if (! is_string($name) || ! is_string($provider) || ! is_subclass_of($provider, ServiceProvider::class)) {
                throw new UnexpectedValueException("config/modules.php: invalid entry for module [{$name}].");
            }

            $modules[$name] = $provider;
        }

        return $modules;
    }

    /**
     * Absolute path of a module's v1 route file.
     */
    public static function routeFile(string $module): string
    {
        return base_path("modules/{$module}/routes/api_v1.php");
    }
}
