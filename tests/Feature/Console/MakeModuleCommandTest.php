<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/*
 * module:make runs against a temporary copy of config/modules.php and
 * deptrac.yaml (--base), so the real project is never changed.
 * The full "generate, composer ci, delete" check is in the README.
 */

beforeEach(function (): void {
    $this->base = sys_get_temp_dir().'/urban-nest-module-make-'.bin2hex(random_bytes(4));

    $files = new Filesystem;
    $files->ensureDirectoryExists($this->base.'/config');
    $files->ensureDirectoryExists($this->base.'/modules');
    $files->copy(base_path('config/modules.php'), $this->base.'/config/modules.php');
    $files->copy(base_path('deptrac.yaml'), $this->base.'/deptrac.yaml');
});

afterEach(function (): void {
    (new Filesystem)->deleteDirectory($this->base);
});

it('creates the full module skeleton', function (): void {
    $this->artisan('module:make', ['name' => 'Payments', '--base' => $this->base])->assertSuccessful();

    $module = $this->base.'/modules/Payments';

    foreach ([
        'Providers/PaymentsServiceProvider.php',
        'Contracts/PaymentsServiceContract.php',
        'DTOs/ExampleData.php',
        'Events/ExampleCreated.php',
        'Services/PaymentsService.php',
        'Http/Controllers/PaymentsController.php',
        'Http/Requests/CreateExampleRequest.php',
        'Http/Resources/ExampleResource.php',
        'Policies/.gitkeep',
        'routes/api_v1.php',
        'tests/Feature/PaymentsExampleEndpointTest.php',
        'tests/Unit/.gitkeep',
    ] as $path) {
        expect($module.'/'.$path)->toBeFile();
    }
});

it('replaces every placeholder and generates valid PHP', function (): void {
    $this->artisan('module:make', ['name' => 'StockTransfers', '--base' => $this->base])->assertSuccessful();

    $phpFiles = (new Filesystem)->allFiles($this->base.'/modules/StockTransfers');

    foreach ($phpFiles as $file) {
        expect($file->getContents())->not->toContain('{{')
            ->and($file->getFilename())->not->toContain('{{');

        if ($file->getExtension() === 'php') {
            exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file->getPathname()).' 2>&1', $output, $exitCode);
            expect($exitCode)->toBe(0, implode("\n", $output));
        }
    }

    expect(file_get_contents($this->base.'/modules/StockTransfers/routes/api_v1.php'))
        ->toContain("Route::prefix('stock-transfers')");
});

it('generates code that passes the project code style (Pint)', function (string $name): void {
    // Import order depends on the module name (Demo sorts before Shared,
    // StockTransfers after it), so check names on both sides.
    $this->artisan('module:make', ['name' => $name, '--base' => $this->base])->assertSuccessful();

    $command = implode(' ', array_map('escapeshellarg', [
        PHP_BINARY,
        base_path('vendor/laravel/pint/builds/pint'),
        '--test',
        '--config='.base_path('pint.json'),
        $this->base.'/modules',
        $this->base.'/config/modules.php',
    ]));
    exec($command.' 2>&1', $output, $exitCode);

    expect($exitCode)->toBe(0, implode("\n", $output));
})->with(['Demo', 'StockTransfers', 'Accounts', 'Zones']);

it('registers the provider in config/modules.php', function (): void {
    $this->artisan('module:make', ['name' => 'Payments', '--base' => $this->base])->assertSuccessful();

    $config = require $this->base.'/config/modules.php';

    expect($config['enabled'])->toHaveKey('Payments', 'Modules\Payments\Providers\PaymentsServiceProvider')
        ->and(file_get_contents($this->base.'/config/modules.php'))->toContain('// module:make providers');
});

it('adds a Deptrac layer and ruleset entry', function (): void {
    $this->artisan('module:make', ['name' => 'Payments', '--base' => $this->base])->assertSuccessful();

    $deptrac = Yaml::parseFile($this->base.'/deptrac.yaml')['deptrac'];
    $layers = collect($deptrac['layers'])->keyBy('name');

    expect($layers)->toHaveKey('Payments')
        ->and($layers['Payments']['collectors'][0]['must'][0]['value'])->toBe('^Modules\\\\Payments\\\\.*')
        ->and($layers['Payments']['collectors'][0]['must_not'][0]['value'])->toBe('ModulesPublic')
        ->and($deptrac['ruleset']['Payments'])->toBe(['Shared', 'ModulesPublic']);

    expect(file_get_contents($this->base.'/deptrac.yaml'))
        ->toContain('# module:make layers')
        ->toContain('# module:make ruleset');
});

it('can add several modules one after another', function (): void {
    $this->artisan('module:make', ['name' => 'Payments', '--base' => $this->base])->assertSuccessful();
    $this->artisan('module:make', ['name' => 'Catalog', '--base' => $this->base])->assertSuccessful();

    $config = require $this->base.'/config/modules.php';
    $deptrac = Yaml::parseFile($this->base.'/deptrac.yaml')['deptrac'];

    // Appended after any modules the project already has, in creation order.
    expect(array_slice(array_keys($config['enabled']), -2))->toBe(['Payments', 'Catalog'])
        ->and($deptrac['ruleset'])->toHaveKeys(['Payments', 'Catalog']);
});

it('refuses to overwrite an existing module and changes nothing', function (): void {
    $this->artisan('module:make', ['name' => 'Payments', '--base' => $this->base])->assertSuccessful();

    $config = file_get_contents($this->base.'/config/modules.php');
    $deptrac = file_get_contents($this->base.'/deptrac.yaml');
    file_put_contents($this->base.'/modules/Payments/marker.txt', 'keep me');

    $this->artisan('module:make', ['name' => 'Payments', '--base' => $this->base])
        ->expectsOutputToContain('already exists')
        ->assertFailed();

    expect(file_get_contents($this->base.'/config/modules.php'))->toBe($config)
        ->and(file_get_contents($this->base.'/deptrac.yaml'))->toBe($deptrac)
        ->and(file_get_contents($this->base.'/modules/Payments/marker.txt'))->toBe('keep me');
});

it('rejects invalid and reserved names', function (string $name): void {
    $this->artisan('module:make', ['name' => $name, '--base' => $this->base])->assertFailed();

    expect((new Filesystem)->directories($this->base.'/modules'))->toBeEmpty();
})->with(['payments', 'Pay-ments', '../Evil', 'Shared', 'App']);

it('writes nothing when a marker is missing', function (): void {
    file_put_contents($this->base.'/deptrac.yaml', str_replace('# module:make ruleset', '', (string) file_get_contents($this->base.'/deptrac.yaml')));
    $config = file_get_contents($this->base.'/config/modules.php');

    $this->artisan('module:make', ['name' => 'Payments', '--base' => $this->base])
        ->expectsOutputToContain('Marker')
        ->assertFailed();

    expect($this->base.'/modules/Payments')->not->toBeDirectory()
        ->and(file_get_contents($this->base.'/config/modules.php'))->toBe($config);
});
