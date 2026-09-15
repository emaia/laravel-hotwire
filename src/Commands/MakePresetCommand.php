<?php

namespace Emaia\LaravelHotwire\Commands;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\CssCustomProperties;
use Emaia\LaravelHotwire\Support\CssPresetFiles;
use Emaia\LaravelHotwire\Support\CssRules;
use Emaia\LaravelHotwire\Support\FoundationFacade;
use Emaia\LaravelHotwire\Support\PresetSkeleton;
use Emaia\LaravelHotwire\Support\PresetSkeletonGroups;
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

    private const string FOUNDATION_IMPORT = '@import "../../../vendor/emaia/laravel-hotwire/resources/css/foundation.css";';

    public function __construct(
        private readonly Filesystem $files,
        private readonly CssPresetFiles $presets,
        private readonly PresetSkeleton $skeleton,
        private readonly PresetSkeletonGroups $skeletonGroups,
        private readonly CssRules $cssRules,
        private readonly CssCustomProperties $customProperties,
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
        $tokens = $this->files->get(dirname(__DIR__, 2).'/resources/css/'.FoundationFacade::TOKEN_SOURCE);
        $sections = ['root' => [], 'light' => [], 'dark' => []];

        foreach ($this->cssRules->parse($tokens) as $rule) {
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
            'root' => $this->customProperties->selectorFor('root'),
            'light' => $this->customProperties->selectorFor('default').",\n".$this->customProperties->selectorFor('light'),
            'dark' => $this->customProperties->selectorFor('dark'),
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

    private function buildScaffold(): ?string
    {
        $tokenTemplate = $this->tokenTemplate();

        if ($tokenTemplate === null) {
            return null;
        }

        $lines = [
            self::FOUNDATION_IMPORT,
            '',
            ...$tokenTemplate,
            '',
            '@layer components {',
            ...$this->skeleton->render($this->skeletonGroups->project(HotwireRegistry::make())),
            '}',
            '',
        ];

        return implode("\n", $lines);
    }

    /**
     * Coalesce default and explicit light declarations because the scaffold emits their selectors together.
     *
     * @return string[]
     */
    private function tokenSections(string $selector): array
    {
        $sections = [];

        foreach ($this->cssRules->splitTopLevel($selector, ',') as $branch) {
            $scope = $this->customProperties->scopeFor($branch, allowConditions: true);
            $section = $scope === 'default' ? 'light' : $scope;

            if ($section !== null && ! in_array($section, $sections, true)) {
                $sections[] = $section;
            }
        }

        return $sections;
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
