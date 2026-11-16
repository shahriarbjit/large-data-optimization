<?php

declare(strict_types=1);

namespace Tests\Architecture;

/**
 * Helpers for the architecture tests. They run before the application boots,
 * so they read the file system directly.
 */
final class ArchHelpers
{
    /**
     * Files Laravel ships in app/ that we keep untouched
     * (docs/decisions/0002-no-database-in-template.md).
     */
    public const array SHIPPED_APP_FILES = [
        'app/Http/Controllers/Controller.php',
        'app/Providers/AppServiceProvider.php',
    ];

    /** The public namespaces of a module. Everything else is internal. */
    public const array PUBLIC_FOLDERS = ['Contracts', 'DTOs', 'Events'];

    /** Folders that hold no classes of the module's namespace. */
    private const array NON_CLASS_FOLDERS = ['routes', 'tests'];

    public static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * Every module folder name except Shared, e.g. ['Auth', 'Orders'].
     *
     * @return list<string>
     */
    public static function modules(): array
    {
        $modules = [];

        foreach (glob(self::root().'/modules/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (basename($dir) !== 'Shared') {
                $modules[] = basename($dir);
            }
        }

        return $modules;
    }

    /**
     * Internal namespaces of a module, e.g. Modules\Orders\Services, Modules\Orders\Http.
     *
     * @return list<string>
     */
    public static function internalNamespaces(string $module): array
    {
        $namespaces = [];

        foreach (glob(self::root()."/modules/{$module}/*", GLOB_ONLYDIR) ?: [] as $dir) {
            $folder = basename($dir);

            if (! in_array($folder, [...self::PUBLIC_FOLDERS, ...self::NON_CLASS_FOLDERS], true)) {
                $namespaces[] = "Modules\\{$module}\\{$folder}";
            }
        }

        // Models do not exist yet, but must never be imported across modules.
        $namespaces[] = "Modules\\{$module}\\Models";

        return array_values(array_unique($namespaces));
    }

    /**
     * Every .php file in app/ and modules/ (including module routes and tests),
     * relative to the project root, without Laravel's shipped app files.
     *
     * @return list<string>
     */
    public static function ownPhpFiles(): array
    {
        $files = [];

        foreach (['app', 'modules'] as $dir) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::root().'/'.$dir, \FilesystemIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(self::root()) + 1));

                if (! in_array($relative, self::SHIPPED_APP_FILES, true)) {
                    $files[] = $relative;
                }
            }
        }

        sort($files);

        return $files;
    }
}
