<?php

namespace Emaia\LaravelHotwire\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class MakeControllerCommand extends Command
{
    public $signature = 'hotwire:make-controller
                        {name : Namespace/name of the controller (e.g. form/autosave)}
                        {--ts : Generate a TypeScript controller}
                        {--force : Overwrite if file already exists}';

    public $description = 'Create a new Stimulus controller';

    private const array VALUE_TYPE_DEFAULTS = [
        'String' => '""',
        'Number' => '0',
        'Boolean' => 'false',
        'Array' => '[]',
        'Object' => '{}',
    ];

    public function __construct(private Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');

        if (! str_contains($name, '/')) {
            warning('Name must include a namespace (e.g. form/autosave, dialog/confirm).');

            return self::FAILURE;
        }

        if (! preg_match('#^[a-z][a-z0-9-]*(/[a-z][a-z0-9-]*)+$#', $name)) {
            warning('Name must contain only lowercase letters, numbers, and hyphens (e.g. form/autosave).');

            return self::FAILURE;
        }

        $ext = $this->resolveExtension();
        $features = $this->resolveFeatures();
        $targets = $this->resolveTargets($features);
        $values = $this->resolveValues($features);
        $classes = $this->resolveClasses($features);

        $content = $this->buildContent($ext, $targets, $values, $classes);
        $filename = $this->toFilename($name, $ext);
        $targetFile = resource_path('js/controllers/'.$filename);

        if ($this->files->exists($targetFile) && ! $this->option('force')) {
            warning("Controller already exists: {$filename}. Use --force to overwrite.");

            return self::FAILURE;
        }

        $this->files->ensureDirectoryExists(dirname($targetFile));
        $this->files->put($targetFile, $content);

        $identifier = $this->toIdentifier($name);

        $this->newLine();
        info("Created: resources/js/controllers/{$filename}");
        $this->line("  Stimulus identifier: {$identifier}");
        $this->line("  Usage: data-controller=\"{$identifier}\"");

        return self::SUCCESS;
    }

    private function resolveExtension(): string
    {
        if ($this->option('ts')) {
            return 'ts';
        }

        if (! $this->input->isInteractive()) {
            return 'js';
        }

        return select(
            label: 'Language?',
            options: ['js' => 'JavaScript', 'ts' => 'TypeScript'],
            default: 'js',
        );
    }

    /** @return string[] */
    private function resolveFeatures(): array
    {
        if (! $this->input->isInteractive()) {
            return [];
        }

        return multiselect(
            label: 'Which features would you like to include?',
            options: ['targets' => 'targets', 'values' => 'values', 'classes' => 'classes'],
        );
    }

    /** @return string[] */
    private function resolveTargets(array $features): array
    {
        if (! in_array('targets', $features)) {
            return [];
        }

        $input = text(
            label: 'Controller targets (comma-separated, e.g. input,button):',
        );

        return array_filter(array_map('trim', explode(',', $input)));
    }

    /** @return array<string, string> */
    private function resolveValues(array $features): array
    {
        if (! in_array('values', $features)) {
            return [];
        }

        $input = text(
            label: 'Value names (comma-separated, e.g. url,count):',
        );

        $names = array_filter(array_map('trim', explode(',', $input)));

        if (empty($names)) {
            return [];
        }

        $values = [];

        foreach ($names as $name) {
            $type = select(
                label: "Type for \"{$name}\"?",
                options: array_keys(self::VALUE_TYPE_DEFAULTS),
                default: 'String',
            );

            $values[$name] = $type;
        }

        return $values;
    }

    /** @return string[] */
    private function resolveClasses(array $features): array
    {
        if (! in_array('classes', $features)) {
            return [];
        }

        $input = text(
            label: 'CSS classes (comma-separated, e.g. active,hidden):',
        );

        return array_filter(array_map('trim', explode(',', $input)));
    }

    /** @param array<string, string> $values */
    private function buildContent(string $ext, array $targets, array $values, array $classes): string
    {
        $lines = ['import { Controller } from "@hotwired/stimulus";', ''];
        $lines[] = 'export default class extends Controller {';

        $members = [];

        if (! empty($targets)) {
            $targetList = implode('", "', $targets);
            $members[] = "    static targets = [\"{$targetList}\"];";
        }

        if (! empty($values)) {
            $members[] = $this->buildValuesBlock($values);
        }

        if (! empty($classes)) {
            $classList = implode('", "', $classes);
            $members[] = "    static classes = [\"{$classList}\"];";
        }

        if ($ext === 'ts' && ! empty($targets)) {
            $members[] = $this->buildTypeScriptDeclarations($targets);
        }

        foreach ($members as $member) {
            $lines[] = $member;
            $lines[] = '';
        }

        $lines[] = '    connect() {}';
        $lines[] = '';
        $lines[] = '    disconnect() {}';
        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /** @param array<string, string> $values */
    private function buildValuesBlock(array $values): string
    {
        $lines = ['    static values = {'];

        $entries = [];
        foreach ($values as $name => $type) {
            $default = self::VALUE_TYPE_DEFAULTS[$type];
            $entries[] = "        {$name}: { type: {$type}, default: {$default} },";
        }

        $lines[] = implode("\n", $entries);
        $lines[] = '    };';

        return implode("\n", $lines);
    }

    /** @param string[] $targets */
    private function buildTypeScriptDeclarations(array $targets): string
    {
        $lines = [];

        foreach ($targets as $target) {
            $lines[] = "    declare readonly {$target}Target: HTMLElement;";
            $lines[] = "    declare readonly has{$this->pascalCase($target)}Target: boolean;";
        }

        return implode("\n", $lines);
    }

    private function pascalCase(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    private function toFilename(string $name, string $ext): string
    {
        $parts = explode('/', $name);
        $controllerName = array_pop($parts);
        $controllerName = str_replace('-', '_', $controllerName);
        $namespace = implode('/', $parts);

        return $namespace.'/'.$controllerName.'_controller.'.$ext;
    }

    private function toIdentifier(string $name): string
    {
        return str($name)
            ->replace('/', '--')
            ->replace('_', '-')
            ->toString();
    }
}
