<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Contracts\HasStimulusControllers;
use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ListComponentsCommand extends Command
{
    public $signature = 'hotwire:components';

    public $description = 'List available Hotwire components and their Stimulus controller dependencies';

    public function __construct(private Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $prefix = config('hotwire.prefix', 'hwc');
        $targetBase = resource_path('js/controllers');
        $sourceBase = realpath(__DIR__.'/../../resources/js/controllers');

        $rows = [];

        foreach (LaravelHotwireServiceProvider::COMPONENTS as $key => $class) {
            $bladeTag = "<x-{$prefix}-{$key}>";
            $componentName = $this->componentName($key);

            if (is_a($class, HasStimulusControllers::class, true)) {
                $controllers = $class::stimulusControllers();
                $firstRow = true;

                foreach ($controllers as $identifier) {
                    $status = $this->resolveStatus($identifier, $targetBase, $sourceBase);

                    $rows[] = [
                        $firstRow ? $componentName : '',
                        $firstRow ? $bladeTag : '',
                        $identifier,
                        $status,
                    ];

                    $firstRow = false;
                }
            } else {
                $rows[] = [$componentName, $bladeTag, '—', '—'];
            }
        }

        $this->table(['Component', 'Blade Tag', 'Controller', 'Status'], $rows);

        return self::SUCCESS;
    }

    private function componentName(string $key): string
    {
        return collect(explode('-', $key))
            ->map(fn ($word) => ucfirst($word))
            ->implode(' ');
    }

    private function resolveStatus(string $identifier, string $targetBase, string $sourceBase): string
    {
        [$dir, $name] = $this->identifierToParts($identifier);

        $filename = "{$name}_controller.js";
        $targetFile = "{$targetBase}/{$dir}/{$filename}";
        $sourceFile = "{$sourceBase}/{$dir}/{$filename}";

        if (! $this->files->exists($targetFile)) {
            return 'not published';
        }

        if (! $this->files->exists($sourceFile)) {
            return 'up to date';
        }

        return $this->files->hash($sourceFile) === $this->files->hash($targetFile)
            ? 'up to date'
            : 'outdated';
    }

    /** @return array{string, string} [relative_dir, name] */
    private function identifierToParts(string $identifier): array
    {
        // 'dialog--modal' → dir='dialog', name='modal'
        // 'notification--toast' → dir='notification', name='toast'
        $parts = explode('--', $identifier, 2);
        $dir = str_replace('--', '/', $parts[0]);
        $name = str_replace('-', '_', $parts[1] ?? '');

        return [$dir, $name];
    }
}
