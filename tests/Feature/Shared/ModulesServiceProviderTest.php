<?php

declare(strict_types=1);

use Modules\Shared\Providers\ModulesServiceProvider;
use Modules\Shared\Providers\SharedServiceProvider;

it('always loads the shared kernel', function (): void {
    expect($this->app->getProviders(SharedServiceProvider::class))->not->toBeEmpty();
});

it('reads enabled modules from config/modules.php', function (): void {
    config(['modules.enabled' => ['Example' => SharedServiceProvider::class]]);

    expect(ModulesServiceProvider::enabledModules())->toBe(['Example' => SharedServiceProvider::class]);
});

it('rejects invalid entries in config/modules.php', function (array $enabled): void {
    config(['modules.enabled' => $enabled]);

    ModulesServiceProvider::enabledModules();
})->with([
    'list instead of map' => [[SharedServiceProvider::class]],
    'not a provider' => [['Example' => stdClass::class]],
    'unknown class' => [['Example' => 'Modules\Nope\Providers\NopeServiceProvider']],
])->throws(UnexpectedValueException::class);
