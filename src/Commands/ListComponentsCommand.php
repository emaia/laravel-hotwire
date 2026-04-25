<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
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
        $registry = HotwireRegistry::make();

        $rows = [];

        foreach ($registry->components() as $component) {
            $bladeTag = $component->tag($prefix);
            $controllers = $registry->controllersForComponent($component);

            if ($controllers === []) {
                $rows[] = [$component->displayName(), $bladeTag, '—', '—'];

                continue;
            }

            $firstRow = true;

            foreach ($controllers as $controller) {
                $rows[] = [
                    $firstRow ? $component->displayName() : '',
                    $firstRow ? $bladeTag : '',
                    $controller->identifier,
                    $this->resolveStatus($controller, $targetBase, $registry->basePath()),
                ];

                $firstRow = false;
            }
        }

        $this->table(['Component', 'Blade Tag', 'Controller', 'Status'], $rows);

        return self::SUCCESS;
    }

    private function resolveStatus($controller, string $targetBase, string $basePath): string
    {
        $sourceFile = $controller->sourcePath($basePath);
        $targetFile = $controller->relativeDir() === ''
            ? "{$targetBase}/{$controller->filename()}"
            : "{$targetBase}/{$controller->relativeDir()}/{$controller->filename()}";

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
}
