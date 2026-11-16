<?php

declare(strict_types=1);

use Tests\Architecture\ArchHelpers;

/*
 * Every PHP file in app/ and modules/ declares strict types, including route
 * files and tests (which Pest's toUseStrictTypes() would not see because they
 * contain no class). Laravel's shipped app/ files are left untouched.
 */
it('declares strict types', function (string $file): void {
    $contents = (string) file_get_contents(ArchHelpers::root().'/'.$file);

    expect($contents)->toContain('declare(strict_types=1);');
})->with(fn (): array => ArchHelpers::ownPhpFiles());
