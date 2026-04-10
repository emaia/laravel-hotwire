<?php

namespace Emaia\LaravelHotwire\Commands;

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
                        {controllers?* : Namespace or namespace/name to publish (e.g. form, form/autoselect)}
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
        if (empty($available)) {
            warning('No controllers available.');

            return self::SUCCESS;
        }

        if ($this->option('list') || ! $this->input->isInteractive()) {
            $targetBase = resource_path('js/controllers');

            $this->table(
                ['Namespace', 'Controller', 'Stimulus Identifier', 'File', 'Status'],
                collect($available)->map(function ($controller) use ($targetBase) {
                    $targetFile = $targetBase.'/'.$controller['relative_dir'].'/'.$controller['filename'];

                    $status = match (true) {
                        ! $this->files->exists($targetFile) => '-',
                        $this->files->hash($controller['source_file']) === $this->files->hash($targetFile) => 'up to date',
                        default => 'outdated',
                    };

                    return [
                        $controller['relative_dir'],
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
                $this->line('  php artisan hotwire:controllers {namespace}      Publish all controllers in a namespace');
                $this->line('  php artisan hotwire:controllers {namespace/name} Publish a specific controller');
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

        foreach ($selected as $key) {
            if (! isset($available[$key])) {
                warning("Controller \"{$key}\" not found. Run --list to see available controllers.");

                continue;
            }

            $controller = $available[$key];
            $targetDir = $targetBase.'/'.$controller['relative_dir'];
            $targetFile = $targetDir.'/'.$controller['filename'];

            if ($this->files->exists($targetFile) && ! $this->option('force')) {
                if ($this->files->hash($controller['source_file']) === $this->files->hash($targetFile)) {
                    info("Controller \"{$key}\" is already up to date.");

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
        }

        if ($published > 0) {
            info("Published {$published} controller(s).");
        }

        return self::SUCCESS;
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
            } else {
                $matched = array_keys(array_filter($available, fn ($c) => $c['relative_dir'] === $arg));

                if (empty($matched)) {
                    warning("Namespace \"{$arg}\" not found. Run --list to see available controllers.");
                } else {
                    array_push($selected, ...$matched);
                }
            }
        }

        return $selected;
    }

    /** @return array<string, array{name: string, identifier: string, relative_dir: string, source_file: string, filename: string}> */
    private function availableControllers(): array
    {
        $baseDir = realpath(__DIR__.'/../../resources/js/controllers');

        if (! $baseDir || ! is_dir($baseDir)) {
            return [];
        }

        $controllers = [];

        $controllerFiles = Finder::create()->files()
            ->name('*_controller.js')
            ->name('*_controller.ts')
            ->in($baseDir);

        foreach ($controllerFiles as $file) {
            $name = preg_replace('/_controller\.(js|ts)$/', '', $file->getFilename());
            $relativeDir = trim(str_replace('\\', '/', $file->getRelativePath()), '/');

            if ($relativeDir === '') {
                continue;
            }

            $identifier = str("{$relativeDir}--{$name}")
                ->replace('/', '--')
                ->replace('_', '-')
                ->toString();

            $key = "{$relativeDir}/{$name}";

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
