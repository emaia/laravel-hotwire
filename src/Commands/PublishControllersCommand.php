<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ControllerImports;
use Emaia\LaravelHotwire\Support\PackageMarker;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\warning;

class PublishControllersCommand extends Command
{
    public $signature = 'hotwire:controllers
                        {controllers?* : Controller name (e.g. modal) or substrate/name (e.g. turbo/progress)}
                        {--all : Publish all available controllers}
                        {--outdated : Update only controllers that are already published but differ from the package source}
                        {--force : Overwrite existing files}
                        {--list : List available controllers}';

    public $description = 'Publish Stimulus controllers to your application';

    public function __construct(
        private readonly Filesystem $files,
        private readonly ControllerImports $imports,
        private readonly PackageMarker $marker,
    ) {
        parent::__construct();
    }

    /**
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $available = $this->availableControllers();

        if ($this->option('list') || (empty($this->argument('controllers')) && ! $this->option('all') && ! $this->option('outdated'))) {
            return $this->listOrSelect($available);
        }

        $selected = match (true) {
            $this->option('all') => array_keys($available),
            $this->option('outdated') => $this->resolveOutdated($available),
            default => $this->resolveArguments($this->argument('controllers'), $available),
        };

        return $this->publishControllers($selected, $available);
    }

    /**
     * @throws FileNotFoundException
     */
    private function listOrSelect(array $available): int
    {
        if ($this->option('list') || ! $this->input->isInteractive()) {
            $targetBase = resource_path('js/controllers');

            $this->table(
                ['Namespace', 'Controller', 'Stimulus Identifier', 'File', 'Status'],
                collect($available)->map(function ($controller) use ($targetBase) {
                    $targetFile = $this->targetFile($targetBase, $controller);

                    $status = match (true) {
                        ! $this->files->exists($targetFile) => '-',
                        $this->files->hash($controller['source_file']) === $this->files->hash($targetFile) => 'up to date',
                        default => 'outdated',
                    };

                    return [
                        $controller['relative_dir'] ?: '(top-level)',
                        $controller['name'],
                        $controller['identifier'],
                        $controller['filename'],
                        $status,
                    ];
                })->toArray()
            );

            if ($this->option('list')) {
                $this->line('');
                $this->line('To publish controllers, run:');
                $this->line('  php artisan hotwire:controllers                  Interactive mode');
                $this->line('  php artisan hotwire:controllers {name}           Publish a top-level controller (e.g. modal)');
                $this->line('  php artisan hotwire:controllers {substrate}      Publish all controllers in a substrate folder (e.g. turbo)');
                $this->line('  php artisan hotwire:controllers {substrate/name} Publish a specific substrate controller');
                $this->line('  php artisan hotwire:controllers --all            Publish all controllers');
                $this->line('  php artisan hotwire:controllers --force          Overwrite existing files');
            }

            return self::SUCCESS;
        }

        $selected = multiselect(
            label: 'Which controllers would you like to publish?',
            options: collect($available)->mapWithKeys(fn ($controller, $key) => [$key => $key])->toArray(),
        );

        if (empty($selected)) {
            info('No controllers selected.');

            return self::SUCCESS;
        }

        return $this->publishControllers($selected, $available);
    }

    /**
     * @throws FileNotFoundException
     */
    private function publishControllers(array $selected, array $available): int
    {
        $targetBase = resource_path('js/controllers');

        $published = 0;
        $publishedDeps = [];

        foreach ($selected as $key) {
            $controller = $available[$key];
            $targetFile = $this->targetFile($targetBase, $controller);
            $targetDir = dirname($targetFile);

            if ($this->files->exists($targetFile)) {
                if ($this->files->hash($controller['source_file']) === $this->files->hash($targetFile)) {
                    info("Controller \"$key\" is already up to date.");

                    $this->publishSharedDeps($controller, $targetBase, $publishedDeps);

                    continue;
                }

                if (! $this->marker->isPackageOwned($targetFile)) {
                    warning("Skipped \"$key\": $targetFile is user-owned (missing package marker). Rename or remove the file, or add `".$this->markerHint($targetFile).'` on its first line to opt in to package updates.');

                    continue;
                }

                if (! $this->option('force')) {
                    if (! $this->input->isInteractive()) {
                        warning("Controller \"$key\" already exists. Use --force to overwrite.");

                        continue;
                    }

                    if (! confirm("Controller \"$key\" already exists and differs from the package version. Overwrite?")) {
                        continue;
                    }
                }
            }

            $this->files->ensureDirectoryExists($targetDir);
            $this->files->copy($controller['source_file'], $targetFile);

            info("Published controller: $key -> $targetFile");
            $published++;

            $published += $this->publishSharedDeps($controller, $targetBase, $publishedDeps);
        }

        if ($published > 0) {
            info("Published $published controller(s).");
        }

        return self::SUCCESS;
    }

    /** @param array<string, bool> $alreadyPublished
     * @throws FileNotFoundException
     */
    private function publishSharedDeps(array $controller, string $targetBase, array &$alreadyPublished): int
    {
        $baseDir = (string) realpath(__DIR__.'/../../resources/js/controllers');
        $count = 0;

        foreach ($this->imports->sharedDependencies($controller['source_file'], $baseDir) as $resolved) {
            $targetFile = $this->imports->targetPath($resolved, $baseDir, $targetBase);
            $relativePath = ltrim(str_replace($targetBase, '', $targetFile), '/');

            if (isset($alreadyPublished[$relativePath])) {
                continue;
            }

            if ($this->files->exists($targetFile)) {
                if ($this->files->hash($resolved) === $this->files->hash($targetFile)) {
                    $alreadyPublished[$relativePath] = true;

                    continue;
                }

                if (! $this->marker->isPackageOwned($targetFile)) {
                    warning("Skipped shared dependency \"$relativePath\": $targetFile is user-owned (missing package marker). Rename or remove the file, or add `".$this->markerHint($targetFile).'` on its first line to opt in to package updates.');
                    $alreadyPublished[$relativePath] = true;

                    continue;
                }

                if (! $this->option('force')) {
                    if (! $this->input->isInteractive()) {
                        warning("Shared dependency \"$relativePath\" already exists. Use --force to overwrite.");
                        $alreadyPublished[$relativePath] = true;

                        continue;
                    }
                }
            }

            $this->files->ensureDirectoryExists(dirname($targetFile));
            $this->files->copy($resolved, $targetFile);

            info('Published dependency: '.basename($resolved).' -> '.$targetFile);
            $alreadyPublished[$relativePath] = true;
            $count++;
        }

        return $count;
    }

    /**
     * A published controller counts as outdated when its own file drifted from
     * the package OR any of its already-published shared dependencies did — so
     * a stale dependency (e.g., carousel.css) is updated even while the
     * controller file itself is unchanged.
     *
     * @return string[]
     *
     * @throws FileNotFoundException
     */
    private function resolveOutdated(array $available): array
    {
        $targetBase = resource_path('js/controllers');
        $baseDir = (string) realpath(__DIR__.'/../../resources/js/controllers');

        return array_keys(array_filter($available, function (array $controller) use ($targetBase, $baseDir): bool {
            $targetFile = $this->targetFile($targetBase, $controller);

            // Not published → not "outdated" (publishing new files is check --fix's job).
            if (! $this->files->exists($targetFile)) {
                return false;
            }

            if ($this->files->hash($controller['source_file']) !== $this->files->hash($targetFile)) {
                return true;
            }

            foreach ($this->imports->sharedDependencies($controller['source_file'], $baseDir) as $depSource) {
                $depTarget = $this->imports->targetPath($depSource, $baseDir, $targetBase);

                if ($this->files->exists($depTarget)
                    && $this->files->hash($depSource) !== $this->files->hash($depTarget)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /** @param string[] $args */
    private function resolveArguments(array $args, array $available): array
    {
        $selected = [];

        foreach ($args as $arg) {
            if (str_contains($arg, '/')) {
                if (! isset($available[$arg])) {
                    warning("Controller \"$arg\" not found. Run --list to see available controllers.");
                } else {
                    $selected[] = $arg;
                }

                continue;
            }

            if (isset($available[$arg])) {
                $selected[] = $arg;

                continue;
            }

            $matched = array_keys(array_filter($available, fn ($c) => $c['relative_dir'] === $arg));

            if (empty($matched)) {
                warning("Controller or substrate \"$arg\" not found. Run --list to see available controllers.");
            } else {
                array_push($selected, ...$matched);
            }
        }

        return $selected;
    }

    /** @param array{relative_dir: string, filename: string} $controller */
    private function targetFile(string $targetBase, array $controller): string
    {
        return $controller['relative_dir'] === ''
            ? $targetBase.'/'.$controller['filename']
            : $targetBase.'/'.$controller['relative_dir'].'/'.$controller['filename'];
    }

    private function markerHint(string $path): string
    {
        return str_ends_with($path, '.css') ? '/* @hotwire-package */' : '// @hotwire-package';
    }

    /** @return array<string, array{name: string, identifier: string, relative_dir: string, source_file: string, filename: string}> */
    private function availableControllers(): array
    {
        $registry = HotwireRegistry::make();
        $basePath = $registry->basePath();

        return array_map(function ($controller) use ($basePath) {
            return [
                'name' => $controller->name(),
                'identifier' => $controller->identifier,
                'relative_dir' => $controller->relativeDir(),
                'source_file' => $controller->sourcePath($basePath),
                'filename' => $controller->filename(),
            ];
        }, $registry->publishableControllers());
    }
}
