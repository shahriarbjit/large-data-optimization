<?php

declare(strict_types=1);

use Tests\Architecture\ArchHelpers;

/*
 * Module boundaries (also enforced by Deptrac, see deptrac.yaml):
 *
 * - Shared depends on no module.
 * - A module may use another module's Contracts, DTOs and Events only.
 * - The public parts (Contracts, DTOs, Events) never reach into internals,
 *   not even of their own module.
 * - app/ (console commands) does not use modules.
 */

$modules = ArchHelpers::modules();

arch('Shared depends on no module')
    ->expect('Modules\Shared')
    ->not->toUse(array_map(fn (string $module): string => "Modules\\{$module}", $modules));

arch('app/ does not use modules (only Shared)')
    ->expect('App')
    ->not->toUse(array_map(fn (string $module): string => "Modules\\{$module}", $modules));

$allInternals = [];
foreach ($modules as $module) {
    $allInternals = [...$allInternals, ...ArchHelpers::internalNamespaces($module)];
}

foreach ($modules as $module) {
    $othersInternals = [];
    foreach (array_diff($modules, [$module]) as $other) {
        $othersInternals = [...$othersInternals, ...ArchHelpers::internalNamespaces($other)];
    }

    if ($othersInternals !== []) {
        arch("{$module} uses other modules' Contracts, DTOs and Events only")
            ->expect("Modules\\{$module}")
            ->not->toUse($othersInternals);
    }

    arch("{$module}: public namespaces do not depend on internals")
        ->expect(array_map(fn (string $folder): string => "Modules\\{$module}\\{$folder}", ArchHelpers::PUBLIC_FOLDERS))
        ->not->toUse($allInternals);
}
