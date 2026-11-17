<?php

declare(strict_types=1);

use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Catalog\Providers\CatalogServiceProvider;
use Modules\Orders\Providers\OrdersServiceProvider;

/*
 * Enabled modules, in load order: module name => its service provider.
 *
 * - The name must match the folder in modules/ and the namespace Modules\{Name}.
 * - Each enabled module's routes/api_v1.php is loaded inside /api/v1.
 * - Shared is the shared kernel and is always loaded first; it is not listed here.
 * - `php artisan module:make Name` adds a line above the marker below.
 *   Do not remove the marker.
 *
 * To disable a module, remove its line. To delete a module, also remove its
 * folder and its two entries in deptrac.yaml.
 */
return [

    'enabled' => [
        'Auth' => AuthServiceProvider::class,
        'Orders' => OrdersServiceProvider::class,
        'Catalog' => CatalogServiceProvider::class,
        // module:make providers
    ],

];
