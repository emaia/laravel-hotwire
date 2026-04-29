<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\DocSearchIndex;
use Emaia\LaravelHotwire\Support\MarkdownRenderer;
use Emaia\LaravelHotwire\Support\OutputPager;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;

use function Laravel\Prompts\search;
use function Laravel\Prompts\select;

class DocsCommand extends Command
{
    public $signature = 'hotwire:docs
                        {name? : Controller or component name (e.g. auto-submit, turbo/progress, modal)}
                        {--list : List available docs entries instead of rendering one}
                        {--pager : Force display through a pager}
                        {--no-pager : Disable pager and print directly}
                        {--controller : Search controllers only}
                        {--component : Search components only}';

    public $description = 'Display documentation for a Hotwire controller or component';

    public function __construct(
        private Filesystem $files,
        private OutputPager $pager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('controller') && $this->option('component')) {
            $this->error('--controller and --component are mutually exclusive.');

            return self::FAILURE;
        }

        if ($this->option('pager') && $this->option('no-pager')) {
            $this->error('--pager and --no-pager are mutually exclusive.');

            return self::FAILURE;
        }

        if ($this->option('list')) {
            if ($this->argument('name')) {
                $this->error('The name argument cannot be used together with --list.');

                return self::FAILURE;
            }

            return $this->renderList(HotwireRegistry::make());
        }

        $registry = HotwireRegistry::make();

        $entry = $this->argument('name')
            ? $this->resolveByName($registry)
            : $this->resolveBySearch($registry);

        if ($entry === null) {
            return self::FAILURE;
        }

        $docPath = $registry->basePath().'/'.$entry['docs'];

        if (! $this->files->exists($docPath)) {
            $this->error("Documentation file not found: {$docPath}");

            return self::FAILURE;
        }

        $lines = [
            ...$this->metadataHeaderLines($entry),
            ...(new MarkdownRenderer)->render($this->files->get($docPath)),
        ];

        $usePager = ! $this->option('no-pager');
        $forcePager = $this->option('pager');

        if ($usePager && $this->pager->page($lines, $this->output, $this->input->isInteractive(), $forcePager)) {
            return self::SUCCESS;
        }

        foreach ($lines as $line) {
            $this->line($line);
        }

        return self::SUCCESS;
    }

    /**
     * @return array{
     *     type: 'controller'|'component',
     *     key: string,
     *     title: string,
     *     label: string,
     *     search: string,
     *     docs: string,
     *     category: string,
     *     description: string,
     *     tag?: string,
     *     npm?: array<string, string>,
     *     controllers?: string[]
     * }|null
     */
    private function resolveByName(HotwireRegistry $registry): ?array
    {
        $name = $this->argument('name');
        $key = str_replace('/', '--', $name);
        $prefix = config('hotwire.prefix', 'hwc');
        $index = new DocSearchIndex;

        $controller = ! $this->option('component') ? $registry->controller($key) : null;
        $component = ! $this->option('controller') ? $registry->component($key) : null;

        if ($controller === null && $component === null) {
            $this->error("\"{$name}\" not found. Run hotwire:controllers --list or hotwire:components to see available names.");

            return null;
        }

        if ($controller !== null && $component !== null) {
            return $this->resolveAmbiguity($name, $registry, $key);
        }

        if ($controller !== null) {
            return $index->forController($controller);
        }

        return $index->forComponent($component, $prefix);
    }

    /**
     * @return array{
     *     type: 'controller'|'component',
     *     key: string,
     *     title: string,
     *     label: string,
     *     search: string,
     *     docs: string,
     *     category: string,
     *     description: string,
     *     tag?: string,
     *     npm?: array<string, string>,
     *     controllers?: string[]
     * }|null
     */
    private function resolveBySearch(HotwireRegistry $registry): ?array
    {
        if (! $this->input->isInteractive()) {
            $this->error('Provide a name argument or run in interactive mode.');

            return null;
        }

        $onlyControllers = $this->option('controller');
        $onlyComponents = $this->option('component');

        $label = match (true) {
            $onlyControllers => 'Search controllers',
            $onlyComponents => 'Search components',
            default => 'Search controllers and components',
        };

        $hint = match (true) {
            $onlyControllers => 'controllers',
            $onlyComponents => 'components',
            default => 'controllers and components',
        };

        $prefix = config('hotwire.prefix', 'hwc');

        $entries = (new DocSearchIndex)->build(
            $registry,
            includeControllers: ! $onlyComponents,
            includeComponents: ! $onlyControllers,
            prefix: $prefix,
        );

        $chosen = search(
            label: $label,
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
            hint: $hint,
        );

        return $entries[array_search($chosen, array_column($entries, 'label'), true)] ?? null;
    }

    /**
     * @return array{
     *     type: 'controller'|'component',
     *     key: string,
     *     title: string,
     *     label: string,
     *     search: string,
     *     docs: string,
     *     category: string,
     *     description: string,
     *     tag?: string,
     *     npm?: array<string, string>,
     *     controllers?: string[]
     * }|null
     */
    private function resolveAmbiguity(string $name, HotwireRegistry $registry, string $key): ?array
    {
        if (! $this->input->isInteractive()) {
            $this->error("Ambiguous name \"{$name}\": exists as both a controller and a component. Use --controller or --component.");

            return null;
        }

        $choice = select(
            label: 'Found in both controllers and components. Which would you like to view?',
            options: ['controller', 'component'],
        );

        $prefix = config('hotwire.prefix', 'hwc');

        $index = new DocSearchIndex;

        if ($choice === 'controller') {
            return $index->forController($registry->requireController($key));
        }

        return $index->forComponent($registry->component($key), $prefix);
    }

    private function renderList(HotwireRegistry $registry): int
    {
        $onlyControllers = $this->option('controller');
        $onlyComponents = $this->option('component');
        $prefix = config('hotwire.prefix', 'hwc');

        $entries = (new DocSearchIndex)->build(
            $registry,
            includeControllers: ! $onlyComponents,
            includeComponents: ! $onlyControllers,
            prefix: $prefix,
        );

        $rows = array_map(function (array $entry): array {
            return [
                ucfirst($entry['type']),
                $entry['type'] === 'component' ? $entry['tag'] : $entry['key'],
                $entry['category'],
                $entry['description'],
            ];
        }, $entries);

        $this->table(['Type', 'Name', 'Category', 'Description'], $rows);

        return self::SUCCESS;
    }

    /**
     * @param  array{
     *     type: 'controller'|'component',
     *     key: string,
     *     title: string,
     *     label: string,
     *     search: string,
     *     docs: string,
     *     category: string,
     *     description: string,
     *     tag?: string,
     *     npm?: array<string, string>,
     *     controllers?: string[]
     * }  $entry
     */
    private function metadataHeaderLines(array $entry): array
    {
        $lines = [
            '',
            sprintf('<options=bold>%s</>', $entry['title']),
            sprintf('Type: <fg=yellow>%s</>', $entry['type']),
            sprintf('Category: <fg=yellow>%s</>', $entry['category']),
        ];

        if ($entry['type'] === 'controller') {
            $lines[] = sprintf('Identifier: <fg=yellow>%s</>', $entry['key']);

            $packages = Arr::get($entry, 'npm', []);

            if ($packages !== []) {
                $lines[] = sprintf('NPM: <fg=yellow>%s</>', implode(', ', array_keys($packages)));
            }
        }

        if ($entry['type'] === 'component') {
            $lines[] = sprintf('Blade: <fg=yellow>%s</>', $entry['tag']);

            $controllers = Arr::get($entry, 'controllers', []);

            if ($controllers !== []) {
                $lines[] = sprintf('Controllers: <fg=yellow>%s</>', implode(', ', $controllers));
            }
        }

        $lines[] = '';

        return $lines;
    }
}
