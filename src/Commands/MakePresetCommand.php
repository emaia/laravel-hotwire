<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\CssPresetFiles;
use Emaia\LaravelHotwire\Support\CssRules;
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
        private readonly CssRules $cssRules,
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

        $content = $source === null ? $this->buildScaffold() : $this->clonePreset($source);

        if ($content === null) {
            return self::FAILURE;
        }

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
     * @return string[]|null
     *
     * @throws FileNotFoundException
     */
    private function tokenTemplate(): ?array
    {
        $tokens = $this->files->get(dirname(__DIR__, 2).'/resources/css/tokens.css');
        $sections = ['root' => [], 'light' => [], 'dark' => []];

        foreach ($this->cssRules->parse($this->cssRules->stripComments($tokens)) as $rule) {
            if (count($rule['chain']) !== 1) {
                continue;
            }

            $rowSections = $this->tokenSections($rule['chain'][0]);

            if ($rowSections === []) {
                continue;
            }

            preg_match_all('/(--[a-z0-9_-]+)\s*:\s*([^;]+?)(?:;|$)/i', $rule['declarations'], $properties, PREG_SET_ORDER);

            foreach ($properties as [, $property, $value]) {
                foreach ($sections as $name => $ignored) {
                    if (in_array($name, $rowSections, true)) {
                        $sections[$name][$property] = trim($value);
                    }
                }
            }
        }

        if (array_filter($sections, fn (array $properties): bool => $properties === []) !== []) {
            warning('Could not extract root, light, and dark token sections from package tokens.css.');

            return null;
        }

        $selectors = [
            'root' => ':root',
            'light' => ":where(:root:not([data-theme=\"dark\"])),\n[data-theme=\"light\"]",
            'dark' => '[data-theme="dark"]',
        ];
        $blocks = [];

        foreach ($sections as $section => $properties) {
            $rows = ["{$selectors[$section]} {"];

            foreach ($properties as $property => $value) {
                $rows[] = "    $property: ".(str_starts_with($value, 'oklch(') ? 'oklch(...)' : '...').';';
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

    private function buildScaffold(): ?string
    {
        $tokenTemplate = $this->tokenTemplate();

        if ($tokenTemplate === null) {
            return null;
        }

        $lines = [
            self::TOKENS_IMPORT,
            self::VARIANTS_IMPORT,
            self::STRUCTURAL_IMPORT,
            '',
            ...$tokenTemplate,
            '',
            '@layer components {',
            ...$this->skeleton->render($this->stylesheets(), $this->groups()),
            '}',
            '',
        ];

        return implode("\n", $lines);
    }

    private function containsThemeSelector(string $selector, string $theme): bool
    {
        return preg_match('/\[data-theme\s*=\s*(["\']?)'.preg_quote($theme, '/').'\1\]/i', $selector) === 1;
    }

    /**
     * Classify a token rule by the themes it declares, ignoring themes it merely negates.
     *
     * A guard such as `:where(:root:not([data-theme="dark"]))` names dark only to exclude it, so the
     * negations are dropped before probing. Each branch of the selector list is classified on its
     * own, since one rule may legitimately declare a value for both themes at once.
     *
     * @return string[]
     */
    private function tokenSections(string $selector): array
    {
        $sections = [];

        foreach ($this->selectorBranches($selector) as $branch) {
            $declared = preg_replace('/:not(\((?:[^()]++|(?1))*\))/i', '', $branch) ?? $branch;
            $section = match (true) {
                $this->containsThemeSelector($declared, 'dark') => 'dark',
                $this->containsThemeSelector($declared, 'light') => 'light',
                trim($branch) === ':root' => 'root',
                default => null,
            };

            if ($section !== null && ! in_array($section, $sections, true)) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    /**
     * Split a selector list on its top-level commas, leaving those inside `:is()`, `:where()` and
     * `:not()` with the branch they qualify.
     *
     * @return string[]
     */
    private function selectorBranches(string $selector): array
    {
        $branches = [];
        $branch = '';
        $depth = 0;

        foreach (str_split($selector) as $character) {
            match ($character) {
                '(' => $depth++,
                ')' => $depth--,
                default => null,
            };

            if ($character === ',' && $depth === 0) {
                $branches[] = $branch;
                $branch = '';

                continue;
            }

            $branch .= $character;
        }

        return array_filter([...$branches, $branch], fn (string $branch): bool => trim($branch) !== '');
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
