<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Registry\ComponentDefinition;
use Emaia\LaravelHotwire\Registry\ControllerDefinition;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\MarkdownRenderer;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\search;
use function Laravel\Prompts\select;

class DocsCommand extends Command
{
    public $signature = 'hotwire:docs
                        {name? : Controller or component name (e.g. auto-submit, turbo/progress, modal)}
                        {--controller : Look up in controllers only}
                        {--component : Look up in components only}';

    public $description = 'Display documentation for a Hotwire controller or component';

    public function __construct(private Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $registry = HotwireRegistry::make();

        $docPath = $this->argument('name')
            ? $this->resolveByName($registry)
            : $this->resolveBySearch($registry);

        if ($docPath === null) {
            return self::FAILURE;
        }

        if (! $this->files->exists($docPath)) {
            $this->error("Documentation file not found: {$docPath}");

            return self::FAILURE;
        }

        foreach ((new MarkdownRenderer)->render($this->files->get($docPath)) as $line) {
            $this->line($line);
        }

        return self::SUCCESS;
    }

    private function resolveByName(HotwireRegistry $registry): ?string
    {
        $name = $this->argument('name');
        $key = str_replace('/', '--', $name);

        $controller = ! $this->option('component') ? $registry->controller($key) : null;
        $component = ! $this->option('controller') ? $registry->component($key) : null;

        if ($controller === null && $component === null) {
            $this->error("\"{$name}\" not found. Run hotwire:controllers --list or hotwire:components to see available names.");

            return null;
        }

        if ($controller !== null && $component !== null) {
            return $this->resolveAmbiguity($name, $registry, $key);
        }

        $entry = $controller ?? $component;

        return $registry->basePath().'/'.$entry->docs;
    }

    private function resolveBySearch(HotwireRegistry $registry): ?string
    {
        if (! $this->input->isInteractive()) {
            $this->error('Provide a name argument or run in interactive mode.');

            return null;
        }

        $entries = $this->buildSearchEntries($registry);

        $chosen = search(
            label: 'Search controllers and components',
            options: function (string $query) use ($entries): array {
                if ($query === '') {
                    return array_column($entries, 'label');
                }

                $q = strtolower($query);

                return array_column(
                    array_filter($entries, fn (array $e) => str_contains($e['search'], $q)),
                    'label'
                );
            },
            placeholder: 'Type a name, category or keyword…',
            hint: 'controllers and components',
        );

        $entry = $entries[array_search($chosen, array_column($entries, 'label'), true)];

        return $registry->basePath().'/'.$entry['docs'];
    }

    /** @return array<int, array{label: string, search: string, docs: string}> */
    private function buildSearchEntries(HotwireRegistry $registry): array
    {
        $entries = [];

        foreach ($registry->controllers() as $controller) {
            $entries[] = $this->controllerEntry($controller);
        }

        foreach ($registry->components() as $component) {
            $entries[] = $this->componentEntry($component);
        }

        return $entries;
    }

    /** @return array{label: string, search: string, docs: string} */
    private function controllerEntry(ControllerDefinition $controller): array
    {
        $label = sprintf(
            '%-26s %-10s  %s',
            $controller->identifier,
            "[{$controller->category}]",
            $controller->description,
        );

        return [
            'label' => $label,
            'search' => strtolower("{$controller->identifier} {$controller->category} {$controller->description} controller"),
            'docs' => $controller->docs,
        ];
    }

    /** @return array{label: string, search: string, docs: string} */
    private function componentEntry(ComponentDefinition $component): array
    {
        $label = sprintf(
            '%-26s %-10s  %s',
            "<x-hwc::{$component->key}>",
            "[{$component->category}]",
            $component->description,
        );

        return [
            'label' => $label,
            'search' => strtolower("{$component->key} {$component->category} {$component->description} component"),
            'docs' => $component->docs,
        ];
    }

    private function resolveAmbiguity(string $name, HotwireRegistry $registry, string $key): ?string
    {
        if (! $this->input->isInteractive()) {
            $this->error("Ambiguous name \"{$name}\": exists as both a controller and a component. Use --controller or --component.");

            return null;
        }

        $choice = select(
            label: 'Found in both controllers and components. Which would you like to view?',
            options: ['controller', 'component'],
        );

        $entry = $choice === 'controller'
            ? $registry->controller($key)
            : $registry->component($key);

        return $registry->basePath().'/'.$entry->docs;
    }
}
