<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Registry\ComponentDefinition;
use Emaia\LaravelHotwire\Registry\ControllerDefinition;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\CssPresetFiles;
use Emaia\LaravelHotwire\Support\PresetAxes;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class MakePresetCommand extends Command
{
    public $signature = 'hotwire:make-preset
                        {name : Name of the preset (e.g. brand, high-contrast)}
                        {--from= : Start from a shipped preset (e.g. nova)}
                        {--force : Overwrite if the file already exists}';

    public $description = 'Create a new CSS preset';

    private const string TOKENS_IMPORT = '@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";';

    private const string VARIANTS_IMPORT = '@import "../../../vendor/emaia/laravel-hotwire/resources/css/custom-variants.css";';

    public function __construct(
        private readonly Filesystem $files,
        private readonly CssPresetFiles $presets,
        private readonly PresetAxes $axes,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        if (! preg_match('/^[a-z][a-z0-9-]*$/', $name)) {
            warning('Name must start with a lowercase letter and contain only lowercase letters, numbers, and hyphens.');

            return self::FAILURE;
        }

        $source = $this->sourcePreset();

        if ($this->option('from') !== null && $source === null) {
            return self::FAILURE;
        }

        $target = resource_path("css/presets/$name.css");

        if ($this->files->exists($target) && ! $this->option('force')) {
            warning("Preset already exists: $name.css. Use --force to overwrite.");

            return self::FAILURE;
        }

        $content = $source === null
            ? $this->buildScaffold()
            : $this->rewritePackageImports($this->files->get($source));

        $this->files->ensureDirectoryExists(dirname($target));
        $this->files->put($target, $content);

        $this->newLine();
        info("Created: resources/css/presets/$name.css");
        $this->line('  Import from resources/css/app.css:');
        $this->line("  @import './presets/$name.css';");

        return self::SUCCESS;
    }

    private function sourcePreset(): ?string
    {
        $name = $this->option('from');

        if ($name === null) {
            return null;
        }

        $source = $this->presets->path((string) $name);

        if ($source !== null) {
            return $source;
        }

        $available = $this->presets->names();
        $hint = $available === [] ? 'No shipped presets were found.' : 'Use one of: '.implode(', ', $available).'.';
        warning("Unknown source preset \"$name\". $hint");

        return null;
    }

    /** Carry the shipped safelist rather than a copy, which would drop the next utility a controller applies. */
    private function runtimeSafelist(): string
    {
        foreach ($this->presets->all() as $path) {
            if (preg_match('/@source inline\("[^"]*"\);/', $this->files->get($path), $match) === 1) {
                return $match[0];
            }
        }

        return '';
    }

    /**
     * Mirror both color schemes from `tokens.css`; a hand-kept copy omits whatever the package adds later.
     *
     * @return string[]
     *
     * @throws FileNotFoundException
     */
    private function tokenTemplate(): array
    {
        $tokens = $this->files->get(dirname(__DIR__, 2).'/resources/css/tokens.css');
        $blocks = [];

        foreach ([':root', '[data-theme="dark"]'] as $selector) {
            if (preg_match('/^'.preg_quote($selector, '/').' \{(.*?)^\}/ms', $tokens, $block) !== 1) {
                continue;
            }

            preg_match_all('/^\s*(--[a-z-]+):\s*([^;]+);/m', $block[1], $properties, PREG_SET_ORDER);
            $rows = ["$selector {"];

            foreach ($properties as [, $property, $value]) {
                $rows[] = "    $property: ".(str_starts_with(trim($value), 'oklch(') ? 'oklch(...)' : '...').';';
            }

            $blocks[] = implode("\n", [...$rows, '}']);
        }

        return [
            '/* Uncomment and replace these values to override the shared theme tokens.',
            implode("\n\n", $blocks),
            '*/',
        ];
    }

    /**
     * Union every preset, so the scaffold describes the vocabulary instead of whichever file sorts first.
     *
     * @return array<string, array<string, string[]>>
     *
     * @throws FileNotFoundException
     */
    private function presetAxes(): array
    {
        $union = [];

        foreach ($this->presets->all() as $path) {
            foreach ($this->axes->extract($this->files->get($path)) as $slot => $axes) {
                foreach ($axes as $axis => $values) {
                    $union[$slot][$axis] = array_values(array_unique([...$union[$slot][$axis] ?? [], ...$values]));
                }
            }
        }

        return $union;
    }

    private function buildScaffold(): string
    {
        $lines = [
            self::TOKENS_IMPORT,
            self::VARIANTS_IMPORT,
            '',
            $this->runtimeSafelist(),
            '',
            ...$this->tokenTemplate(),
            '',
            '@layer components {',
        ];
        $emitted = [];
        $axes = $this->presetAxes();
        $registry = HotwireRegistry::make();

        foreach ($registry->components() as $component) {
            $this->appendGroup($lines, $component, $emitted, $axes);
        }

        foreach ($registry->controllers() as $controller) {
            $this->appendGroup($lines, $controller, $emitted, $axes);
        }

        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * @param  string[]  $lines
     * @param  array<string, true>  $emitted
     * @param  array<string, array<string, string[]>>  $axes
     */
    private function appendGroup(array &$lines, ComponentDefinition|ControllerDefinition $definition, array &$emitted, array $axes): void
    {
        $visual = array_values(array_filter(
            $definition->styling->visualSlots(),
            fn (string $slot): bool => ! isset($emitted[$slot]),
        ));

        if ($visual === []) {
            return;
        }

        $lines[] = '';
        $lines[] = '    /* '.($definition instanceof ComponentDefinition
                ? $definition->displayName()
                : str($definition->identifier)->replace('--', ' ')->replace('-', ' ')->title().' controller').' */';

        foreach ($visual as $slot) {
            foreach ($axes[$slot] ?? [] as $axis => $values) {
                $lines[] = "    /* data-$axis: ".implode(', ', $values).' */';
            }

            $lines[] = "    [data-slot=\"$slot\"] {}";
            $emitted[$slot] = true;
        }
    }

    private function rewritePackageImports(string $content): string
    {
        return str_replace(
            ['@import "../tokens.css";', '@import "../custom-variants.css";'],
            [self::TOKENS_IMPORT, self::VARIANTS_IMPORT],
            $content,
        );
    }
}
