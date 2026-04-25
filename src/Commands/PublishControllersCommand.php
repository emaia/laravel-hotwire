<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Registry\ControllerDefinition;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\warning;

class PublishControllersCommand extends Command
{
    public $signature = 'hotwire:controllers
                        {controllers?* : Controller name (e.g. dialog) or substrate/name (e.g. turbo/progress)}
                        {--all : Publish all available controllers}
                        {--force : Overwrite existing files}
                        {--list : List available controllers}';

    public $description = 'Publish Stimulus controllers to your application';

    public function __construct(private Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $available = $this->availableControllers();

        if ($this->option('list') || (empty($this->argument('controllers')) && ! $this->option('all'))) {
            return $this->listOrSelect($available);
        }

        $selected = $this->option('all')
            ? array_keys($available)
            : $this->resolveArguments($this->argument('controllers'), $available);

        return $this->publishControllers($selected, $available);
    }

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
                $this->line('  php artisan hotwire:controllers {name}           Publish a top-level controller (e.g. dialog)');
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

    private function publishControllers(array $selected, array $available): int
    {
        $targetBase = resource_path('js/controllers');

        $published = 0;
        $publishedDeps = [];

        foreach ($selected as $key) {
            $controller = $available[$key];
            $targetFile = $this->targetFile($targetBase, $controller);
            $targetDir = dirname($targetFile);

            if ($this->files->exists($targetFile) && ! $this->option('force')) {
                if ($this->files->hash($controller['source_file']) === $this->files->hash($targetFile)) {
                    info("Controller \"{$key}\" is already up to date.");

                    $this->publishSharedDeps($controller, $targetBase, $publishedDeps);

                    continue;
                }

                if (! $this->input->isInteractive()) {
                    warning("Controller \"{$key}\" already exists. Use --force to overwrite.");

                    continue;
                }

                if (! confirm("Controller \"{$key}\" already exists and differs from the package version. Overwrite?")) {
                    continue;
                }
            }

            $this->files->ensureDirectoryExists($targetDir);
            $this->files->copy($controller['source_file'], $targetFile);

            info("Published controller: {$key} -> {$targetFile}");
            $published++;

            $published += $this->publishSharedDeps($controller, $targetBase, $publishedDeps);
        }

        if ($published > 0) {
            info("Published {$published} controller(s).");
        }

        return self::SUCCESS;
    }

    /** @param array<string, bool> $alreadyPublished */
    private function publishSharedDeps(array $controller, string $targetBase, array &$alreadyPublished): int
    {
        $baseDir = (string) realpath(__DIR__.'/../../resources/js/controllers');

        $count = 0;
        $visited = [];
        $queue = [$controller['source_file']];

        while (! empty($queue)) {
            $current = array_shift($queue);

            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            foreach ($this->extractRelativeImports($current) as $importPath) {
                $resolved = $this->resolveImport($current, $importPath);

                if (! $resolved || ! str_starts_with($resolved, $baseDir.DIRECTORY_SEPARATOR)) {
                    continue;
                }

                // Other controllers are published independently — skip here
                if (preg_match('/_controller\.(js|ts)$/', $resolved)) {
                    continue;
                }

                $relativePath = ltrim(str_replace('\\', '/', substr($resolved, strlen($baseDir))), '/');
                $targetFile = $targetBase.'/'.$relativePath;

                if (isset($alreadyPublished[$relativePath])) {
                    $queue[] = $resolved;

                    continue;
                }

                if ($this->files->exists($targetFile) && ! $this->option('force')) {
                    if ($this->files->hash($resolved) === $this->files->hash($targetFile)) {
                        $alreadyPublished[$relativePath] = true;
                        $queue[] = $resolved;

                        continue;
                    }

                    if (! $this->input->isInteractive()) {
                        continue;
                    }
                }

                $this->files->ensureDirectoryExists(dirname($targetFile));
                $this->files->copy($resolved, $targetFile);

                info('Published dependency: '.basename($resolved).' -> '.$targetFile);
                $alreadyPublished[$relativePath] = true;
                $count++;
                $queue[] = $resolved;
            }
        }

        return $count;
    }

    /** @return string[] */
    private function extractRelativeImports(string $filePath): array
    {
        $source = $this->files->get($filePath);
        $imports = [];

        $patterns = [
            '/\b(?:import|from|require)\s*\(?\s*[\'"](\.[^\'"]+)[\'"]/',
            '/@import\s+(?:url\()?\s*[\'"](\.[^\'"]+)[\'"]/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $source, $matches)) {
                $imports = array_merge($imports, $matches[1]);
            }
        }

        return array_values(array_unique($imports));
    }

    private function resolveImport(string $fromFile, string $importPath): ?string
    {
        $candidate = dirname($fromFile).'/'.$importPath;

        $direct = realpath($candidate);
        if ($direct && is_file($direct)) {
            return $direct;
        }

        foreach (['.js', '.ts', '.mjs', '.css'] as $ext) {
            $withExt = realpath($candidate.$ext);
            if ($withExt && is_file($withExt)) {
                return $withExt;
            }
        }

        foreach (['.js', '.ts', '.mjs'] as $ext) {
            $index = realpath($candidate.'/index'.$ext);
            if ($index && is_file($index)) {
                return $index;
            }
        }

        return null;
    }

    /** @param string[] $args */
    private function resolveArguments(array $args, array $available): array
    {
        $selected = [];

        foreach ($args as $arg) {
            if (str_contains($arg, '/')) {
                if (! isset($available[$arg])) {
                    warning("Controller \"{$arg}\" not found. Run --list to see available controllers.");
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
                warning("Controller or substrate \"{$arg}\" not found. Run --list to see available controllers.");
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

    /** @return array<string, array{name: string, identifier: string, relative_dir: string, source_file: string, filename: string}> */
    private function availableControllers(): array
    {
        $controllers = [];
        $registry = HotwireRegistry::make();
        $basePath = $registry->basePath();
        $baseDir = $basePath.'/resources/js/controllers';

        foreach ($registry->publishableControllers() as $key => $controller) {
            $controllers[$key] = [
                'name' => $controller->name(),
                'identifier' => $controller->identifier,
                'relative_dir' => $controller->relativeDir(),
                'source_file' => $controller->sourcePath($basePath),
                'filename' => $controller->filename(),
            ];
        }

        $controllerFiles = Finder::create()->files()
            ->name('*_controller.js')
            ->name('*_controller.ts')
            ->in($baseDir);

        foreach ($controllerFiles as $file) {
            $name = preg_replace('/_controller\.(js|ts)$/', '', $file->getFilename());
            $relativeDir = trim(str_replace('\\', '/', $file->getRelativePath()), '/');
            $key = $relativeDir === '' ? $name : "{$relativeDir}/{$name}";

            if (isset($controllers[$key])) {
                continue;
            }

            $identifier = $relativeDir === ''
                ? str($name)->replace('_', '-')->toString()
                : str("{$relativeDir}--{$name}")->replace('/', '--')->replace('_', '-')->toString();

            $controllers[$key] = [
                'name' => $name,
                'identifier' => $identifier,
                'relative_dir' => $relativeDir,
                'source_file' => $file->getRealPath(),
                'filename' => $file->getFilename(),
            ];
        }

        uksort($controllers, function ($a, $b) use ($controllers) {
            $cmp = strcmp($controllers[$a]['relative_dir'], $controllers[$b]['relative_dir']);

            return $cmp !== 0 ? $cmp : strcmp($a, $b);
        });

        return $controllers;
    }
}
