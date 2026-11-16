<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

/**
 * php artisan module:make Payments
 *
 * 1. Copies stubs/module/ to modules/Payments/, replacing the placeholders.
 * 2. Adds the provider to config/modules.php (above "// module:make providers").
 * 3. Adds a Deptrac layer and ruleset entry to deptrac.yaml
 *    (above "# module:make layers" and "# module:make ruleset").
 *
 * It refuses to overwrite an existing module, and checks every target before
 * writing anything, so a failed run leaves no half-made module behind.
 *
 * --base is for tests: it points the command at a copy of the project.
 */
final class MakeModuleCommand extends Command
{
    /** @var string */
    protected $signature = 'module:make
        {name : Module name in StudlyCase, e.g. Payments}
        {--base= : Project root to write into (default: this project; used by tests)}';

    /** @var string */
    protected $description = 'Create a new module skeleton in modules/ and register it';

    private const array RESERVED = ['Shared', 'App', 'Modules', 'Tests'];

    private const string CONFIG_MARKER = '        // module:make providers';

    private const string DEPTRAC_LAYERS_MARKER = '    # module:make layers';

    private const string DEPTRAC_RULESET_MARKER = '    # module:make ruleset';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->makeModule();
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function makeModule(): void
    {
        $name = $this->moduleName();
        $base = $this->basePath();

        $moduleDir = "{$base}/modules/{$name}";
        $configFile = "{$base}/config/modules.php";
        $deptracFile = "{$base}/deptrac.yaml";
        $stubsDir = base_path('stubs/module');

        // Check everything first; write nothing until all checks pass.
        if ($this->files->exists($moduleDir)) {
            throw new RuntimeException("Module [{$name}] already exists at modules/{$name}. Nothing was changed.");
        }

        $config = $this->readWithMarker($configFile, self::CONFIG_MARKER);
        $deptrac = $this->readWithMarker($deptracFile, self::DEPTRAC_LAYERS_MARKER);
        $this->readWithMarker($deptracFile, self::DEPTRAC_RULESET_MARKER);

        if (! $this->files->isDirectory($stubsDir)) {
            throw new RuntimeException("Stub folder not found: {$stubsDir}");
        }

        $replacements = $this->replacements($name);

        // 1. Module files.
        $created = $this->copyStubs($stubsDir, $moduleDir, $replacements);

        // 2. config/modules.php
        $provider = "{$name}ServiceProvider";
        $config = $this->insertBefore($config, self::CONFIG_MARKER, "        '{$name}' => {$provider}::class,");
        $config = $this->addImport($config, "Modules\\{$name}\\Providers\\{$provider}");
        $this->files->put($configFile, $config);

        // 3. deptrac.yaml
        $deptrac = $this->insertBefore($deptrac, self::DEPTRAC_LAYERS_MARKER, $this->deptracLayer($name));
        $deptrac = $this->insertBefore($deptrac, self::DEPTRAC_RULESET_MARKER, $this->deptracRuleset($name));
        $this->files->put($deptracFile, $deptrac);

        $this->components->info("Module [{$name}] created.");
        $this->components->bulletList([
            ...array_map(fn (string $path): string => "modules/{$name}/{$path}", $created),
            'config/modules.php (provider registered)',
            'deptrac.yaml (layer and ruleset added)',
        ]);
        $this->components->info('Next: rename the Example* classes, then run `composer ci`.');
    }

    private function moduleName(): string
    {
        $name = $this->argument('name');

        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $name) !== 1) {
            throw new RuntimeException('The module name must be StudlyCase letters and digits, e.g. Payments.');
        }

        if (in_array($name, self::RESERVED, true)) {
            throw new RuntimeException("[{$name}] is a reserved name.");
        }

        return $name;
    }

    private function basePath(): string
    {
        $base = $this->option('base');

        return is_string($base) && $base !== '' ? rtrim($base, '/\\') : base_path();
    }

    private function readWithMarker(string $path, string $marker): string
    {
        if (! $this->files->isFile($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        $contents = $this->files->get($path);

        if (! str_contains($contents, $marker)) {
            throw new RuntimeException('Marker "'.trim($marker)."\" not found in {$path}. Restore it and try again.");
        }

        return $contents;
    }

    private function insertBefore(string $contents, string $marker, string $text): string
    {
        return str_replace($marker, $text."\n".$marker, $contents);
    }

    /**
     * @return array<string, string>
     */
    private function replacements(string $name): array
    {
        return [
            '{{Module}}' => $name,
            '{{module-kebab}}' => Str::kebab($name),
            '{{moduleCamel}}' => Str::camel($name),
        ];
    }

    /**
     * Copies every file under stubs/module/. "Name.php.stub" becomes "Name.php";
     * placeholders are replaced in both paths and contents.
     *
     * @param  array<string, string>  $replacements
     * @return list<string> created paths, relative to the module folder
     */
    private function copyStubs(string $stubsDir, string $moduleDir, array $replacements): array
    {
        $created = [];

        /** @var SplFileInfo $stub */
        foreach ($this->files->allFiles($stubsDir, hidden: true) as $stub) {
            $relative = strtr(str_replace('\\', '/', $stub->getRelativePathname()), $replacements);
            $relative = Str::replaceLast('.stub', '', $relative);
            $target = "{$moduleDir}/{$relative}";

            $contents = strtr($stub->getContents(), $replacements);

            if (str_ends_with($relative, '.php')) {
                $contents = $this->sortImports($contents);
            }

            $this->files->ensureDirectoryExists(dirname($target));
            $this->files->put($target, $contents);

            $created[] = $relative;
        }

        sort($created);

        return $created;
    }

    /**
     * Adds "use $class;" to a PHP file: into its existing import block, or
     * after declare(strict_types=1) when there is none yet.
     */
    private function addImport(string $php, string $class): string
    {
        $line = "use {$class};\n";

        if (preg_match('/^use [^;]+;\n/m', $php) === 1) {
            $php = (string) preg_replace_callback('/^use [^;]+;\n/m', fn (array $m): string => $line.$m[0], $php, 1);
        } elseif (str_contains($php, "declare(strict_types=1);\n")) {
            $php = str_replace("declare(strict_types=1);\n", "declare(strict_types=1);\n\n".$line, $php);
        } else {
            throw new RuntimeException('Cannot add an import: no "declare(strict_types=1);" line found.');
        }

        return $this->sortImports($php);
    }

    /**
     * Sorts the first block of top-level "use" lines the way Pint does
     * (ordered_imports, alpha: case-insensitive, "\" compared as a space),
     * so generated code passes `composer lint` whatever the module name is.
     */
    private function sortImports(string $php): string
    {
        return (string) preg_replace_callback('/^(?:use [^;]+;\n)+/m', function (array $m): string {
            $lines = array_unique(explode("\n", rtrim($m[0], "\n")));

            usort($lines, fn (string $a, string $b): int => strcasecmp(
                str_replace('\\', ' ', $a),
                str_replace('\\', ' ', $b),
            ));

            return implode("\n", $lines)."\n";
        }, $php, 1);
    }

    private function deptracLayer(string $name): string
    {
        return <<<YAML
            - name: {$name}
              collectors:
                - type: bool
                  must:
                    - type: classLike
                      value: ^Modules\\\\{$name}\\\\.*
                  must_not:
                    - type: layer
                      value: ModulesPublic

        YAML;
    }

    private function deptracRuleset(string $name): string
    {
        return <<<YAML
            {$name}:
              - Shared
              - ModulesPublic
        YAML;
    }
}
