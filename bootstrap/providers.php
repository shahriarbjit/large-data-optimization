<?php

use App\Providers\AppServiceProvider;
use Modules\Shared\Providers\ModulesServiceProvider;

return [
    AppServiceProvider::class,
    // Loads Shared and every module listed in config/modules.php.
    ModulesServiceProvider::class,
];
