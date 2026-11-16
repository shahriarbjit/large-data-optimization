<?php

declare(strict_types=1);

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Modules\Shared\Exceptions\ApiException;
use Modules\Shared\Exceptions\ExceptionRenderer;
use Modules\Shared\Http\Requests\BaseFormRequest;
use Tests\Architecture\ArchHelpers;

/*
 * Conventions every module follows (docs/architecture.md).
 */

arch('classes are final by default (abstract base classes excepted)')
    ->expect(['App', 'Modules'])
    ->classes()
    ->toBeFinal()
    ->ignoring([
        ApiException::class,
        BaseFormRequest::class,
        // Laravel's shipped app/ files are left untouched.
        'App\Http\Controllers\Controller',
        'App\Providers\AppServiceProvider',
    ]);

arch('no models in app/: each model lives in its module (Modules\{Module}\Models)')
    ->expect('App')
    ->not->toExtend('Illuminate\Database\Eloquent\Model');

arch('Shared exceptions extend ApiException')
    ->expect('Modules\Shared\Exceptions')
    ->classes()
    ->toExtend(ApiException::class)
    ->ignoring([ApiException::class, ExceptionRenderer::class]);

foreach (ArchHelpers::modules() as $module) {
    arch("{$module}: controllers are final, suffixed and thin (no Eloquent, no DB)")
        ->expect("Modules\\{$module}\\Http\\Controllers")
        ->toBeFinal()
        ->toHaveSuffix('Controller')
        ->not->toUse([
            'Illuminate\Database',
            'Illuminate\Support\Facades\DB',
            'App\Models',
            "Modules\\{$module}\\Models",
        ]);

    arch("{$module}: controllers depend on the module contract, not on the service class")
        ->expect("Modules\\{$module}\\Http\\Controllers")
        ->not->toUse("Modules\\{$module}\\Services");

    arch("{$module}: contracts are interfaces")
        ->expect("Modules\\{$module}\\Contracts")
        ->toBeInterfaces()
        ->toHaveSuffix('Contract');

    // DTOs may also hold enums (e.g. OrderStatus); ->classes() leaves them out.
    // One expectation per arch() call: the ->classes() filter applies to one expectation.
    arch("{$module}: DTO classes are final")
        ->expect("Modules\\{$module}\\DTOs")
        ->classes()
        ->toBeFinal();

    arch("{$module}: DTO classes are readonly")
        ->expect("Modules\\{$module}\\DTOs")
        ->classes()
        ->toBeReadonly();

    arch("{$module}: events are final readonly classes")
        ->expect("Modules\\{$module}\\Events")
        ->toBeFinal()
        ->toBeReadonly();

    arch("{$module}: form requests extend BaseFormRequest")
        ->expect("Modules\\{$module}\\Http\\Requests")
        ->toExtend(BaseFormRequest::class)
        ->toHaveSuffix('Request');

    arch("{$module}: resources extend JsonResource")
        ->expect("Modules\\{$module}\\Http\\Resources")
        ->toExtend(JsonResource::class)
        ->toHaveSuffix('Resource');

    arch("{$module}: only Models extend Eloquent Model")
        ->expect("Modules\\{$module}")
        ->not->toExtend('Illuminate\Database\Eloquent\Model')
        ->ignoring("Modules\\{$module}\\Models");

    arch("{$module}: models are used only by the module's Services, Database and Providers")
        ->expect("Modules\\{$module}\\Models")
        ->toOnlyBeUsedIn([
            "Modules\\{$module}\\Services",
            "Modules\\{$module}\\Database",
            "Modules\\{$module}\\Models",
            "Modules\\{$module}\\Providers",
        ]);

    arch("{$module}: providers extend ServiceProvider")
        ->expect("Modules\\{$module}\\Providers")
        ->toExtend(ServiceProvider::class)
        ->toHaveSuffix('ServiceProvider');
}
