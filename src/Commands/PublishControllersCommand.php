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
                        {--force : Overwrite existing controllers}
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
                ['Controller', 'File'],
                collect($available)->map(fn ($controller, $name) => [$name, $controller['filename']])->toArray()
            );

            return self::SUCCESS;
        }

        $selected = multiselect(
            label: 'Which controllers would you like to publish?',
            options: array_keys($available),
        );

        if (empty($selected)) {
            info('No controllers selected.');

            return self::SUCCESS;
        }

        return $this->publishControllers($selected, $available);
    }

    private function publishControllers(array $selected, array $available): int
    {
        $targetDir = resource_path('js/controllers');
        $this->files->ensureDirectoryExists($targetDir);

        $published = 0;

        foreach ($selected as $name) {
            $name = str($name)->before('_controller')->toString();

            if (! isset($available[$name])) {
                warning("Controller \"{$name}\" not found. Available: " . implode(', ', array_keys($available)));

                continue;
            }

            $controller = $available[$name];
            $target = $targetDir . '/' . $controller['filename'];

            if ($this->files->exists($target) && ! $this->option('force')) {
                if ($this->files->get($controller['path']) === $this->files->get($target)) {
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

            $this->files->copy($controller['path'], $target);
            info("Published controller: {$name} -> {$target}");
            $published++;
        }

        if ($published > 0) {
            info("Published {$published} controller(s).");
        }

        return self::SUCCESS;
    }

    /** @return array<string, array{path: string, filename: string}> name => controller info */
    private function availableControllers(): array
    {
        $dir = realpath(__DIR__ . '/../../resources/js/controllers');

        if (! $dir || ! is_dir($dir)) {
            return [];
        }

        $controllers = [];
        $files = Finder::create()->files()->name('*_controller.js')->in($dir);

        foreach ($files as $file) {
            $name = str($file->getFilename())->before('_controller.js')->toString();
            $controllers[$name] = [
                'path' => $file->getRealPath(),
                'filename' => $file->getFilename(),
            ];
        }

        ksort($controllers);

        return $controllers;
    }
}
