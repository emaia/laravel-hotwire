<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\CssPresetFiles;
use Emaia\LaravelHotwire\Support\PresetSkeleton;
use Emaia\LaravelHotwire\Support\PresetSource;
use Emaia\LaravelHotwire\Support\PresetSourceException;
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

    private const string STRUCTURAL_IMPORT = '@import "../../../vendor/emaia/laravel-hotwire/resources/css/structural.css";';

    public function __construct(
        private readonly Filesystem $files,
        private readonly CssPresetFiles $presets,
        private readonly PresetSkeleton $skeleton,
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
            : $this->clonePreset($source);

        $this->files->ensureDirectoryExists(dirname($target));
        $this->files->put($target, $content);

        $this->newLine();
        info("Created: resources/css/presets/$name.css");
        $this->line('  Import from resources/css/app.css:');
        $this->line("  @import './presets/$name.css';");

        return self::SUCCESS;
    }

    private function sourcePreset(): ?PresetSource
    {
        $name = $this->option('from');

        if ($name === null) {
            return null;
        }

        try {
            $source = $this->presets->source((string) $name);
        } catch (PresetSourceException $exception) {
            warning($exception->getMessage());

            return null;
        }

        if ($source !== null) {
            return $source;
        }

        $available = $this->presets->names();
        $hint = $available === [] ? 'No shipped presets were found.' : 'Use one of: '.implode(', ', $available).'.';
        warning("Unknown source preset \"$name\". $hint");

        return null;
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
     * @return string[]
     *
     * @throws FileNotFoundException
     */
    private function stylesheets(): array
    {
        $stylesheets = [];

        foreach ($this->presets->names() as $preset) {
            $stylesheets = [...$stylesheets, ...$this->presets->source($preset)?->visualStylesheets() ?? []];
        }

        return $stylesheets;
    }

    /**
     * Ordered label => visual slots, the grouping the scaffold is laid out by.
     *
     * @return array<string, string[]>
     */
    private function groups(): array
    {
        $registry = HotwireRegistry::make();
        $groups = [];

        foreach ($registry->components() as $component) {
            $label = $component->displayName();
            $groups[$label] = [...$groups[$label] ?? [], ...$component->styling->visualSlots()];
        }

        foreach ($registry->controllers() as $controller) {
            $label = str($controller->identifier)->replace('--', ' ')->replace('-', ' ')->title().' controller';
            $groups[$label] = [...$groups[$label] ?? [], ...$controller->styling->visualSlots()];
        }

        return $groups;
    }

    private function buildScaffold(): string
    {
        $lines = [
            self::TOKENS_IMPORT,
            self::VARIANTS_IMPORT,
            self::STRUCTURAL_IMPORT,
            '',
            ...$this->tokenTemplate(),
            '',
            '@layer components {',
            ...$this->skeleton->render($this->stylesheets(), $this->groups()),
            '}',
            '',
        ];

        return implode("\n", $lines);
    }

    private function clonePreset(PresetSource $source): string
    {
        $imports = array_map(
            fn (string $path): string => "@import \"../../../vendor/emaia/laravel-hotwire/resources/css/{$path}\";",
            $source->foundationImports(),
        );

        return implode("\n", [...$imports, '', $source->visualCss(), '']);
    }
}
