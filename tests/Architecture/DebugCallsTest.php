<?php

declare(strict_types=1);

/*
 * Code hygiene. These checks cover the App, Modules and Tests namespaces.
 */

arch('no debug helpers')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray', 'ddd'])
    ->not->toBeUsed();

arch('env() is only used in config/ (read settings with config())')
    ->expect('env')
    ->not->toBeUsed();

arch('no die or exit')
    ->expect(['die', 'exit'])
    ->not->toBeUsed();
