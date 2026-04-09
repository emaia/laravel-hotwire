<?php

namespace Emaia\LaravelHotwireComponents\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\warning;

class PublishControllersCommand extends Command
{
    public $signature = 'hwc:controllers
                        {controllers?* : Controller names to publish (e.g. modal)}
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
            : $this->argument('controllers');

        return $this->publishControllers($selected, $available);
    }

    private function listOrSelect(array $available): int
    {
        if (empty($available)) {
            warning('No controllers available.');

            return self::SUCCESS;
        }

        if (! $this->input->isInteractive()) {
            $this->table(
                ['Controller', 'Stimulus Identifier', 'Files'],
                collect($available)->map(fn ($controller, $name) => [
                    $name,
                    $controller['identifier'],
                    implode(', ', array_map('basename', $controller['files'])),
                ])->toArray()
            );

            return self::SUCCESS;
        }

        $selected = multiselect(
            label: 'Which controllers would you like to publish?',
            options: collect($available)->mapWithKeys(fn ($controller, $name) => [
                $name => $controller['relative_dir'] !== ''
                    ? "[{$controller['relative_dir']}] {$name}"
                    : $name,
            ])->toArray(),
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
        $this->files->ensureDirectoryExists($targetBase);

        $published = 0;

        foreach ($selected as $name) {
            if (! isset($available[$name])) {
                warning("Controller \"{$name}\" not found. Available: ".implode(', ', array_keys($available)));

                continue;
            }

            $controller = $available[$name];
            $targetDir = $controller['relative_dir'] !== ''
                ? $targetBase.'/'.$controller['relative_dir']
                : $targetBase;

            if ($this->files->isDirectory($targetDir) && ! $this->option('force')) {
                if ($this->directoryContentsMatch($controller['source_dir'], $targetDir)) {
                    info("Controller \"{$name}\" is already up to date.");

                    continue;
                }

                if (! $this->input->isInteractive()) {
                    warning("Controller \"{$name}\" already exists. Use --force to overwrite.");

                    continue;
                }

                if (! confirm("Controller \"{$name}\" already exists and differs from the package version. Overwrite?")) {
                    continue;
                }
            }

            $this->files->ensureDirectoryExists($targetDir);
            $this->files->copyDirectory($controller['source_dir'], $targetDir);
            info("Published controller: {$name} -> {$targetDir}");
            $published++;
        }

        if ($published > 0) {
            info("Published {$published} controller(s).");
        }

        return self::SUCCESS;
    }

    private function directoryContentsMatch(string $source, string $target): bool
    {
        $sourceFiles = Finder::create()->files()->in($source)->sortByName();

        foreach ($sourceFiles as $file) {
            $targetFile = $target.'/'.$file->getRelativePathname();

            if (! $this->files->exists($targetFile)) {
                return false;
            }

            if ($this->files->get($file->getRealPath()) !== $this->files->get($targetFile)) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, array{identifier: string, relative_dir: string, source_dir: string, files: list<string>}> */
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

            $allFiles = Finder::create()->files()->in($file->getPath())->sortByName();

            $identifier = str($relativeDir !== '' ? "{$relativeDir}--{$name}" : $name)
                ->replace('/', '--')
                ->replace('_', '-')
                ->toString();

            $controllers[$name] = [
                'identifier' => $identifier,
                'relative_dir' => $relativeDir,
                'source_dir' => $file->getPath(),
                'files' => array_values(array_map(fn ($f) => $f->getRealPath(), iterator_to_array($allFiles))),
            ];
        }

        uksort($controllers, function ($a, $b) use ($controllers) {
            $cmp = strcmp($controllers[$a]['relative_dir'], $controllers[$b]['relative_dir']);

            return $cmp !== 0 ? $cmp : strcmp($a, $b);
        });

        return $controllers;
    }
}
