<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\CssPresetFiles;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->appBase = isolateAppPaths();
    $this->targetDir = resource_path('css/presets');
});

afterEach(function () {
    releaseIsolatedAppPaths($this->appBase);
});

it('scaffolds every visual catalog slot once without structural slots', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    $path = $this->targetDir.'/brand.css';
    $css = File::get($path);
    $registry = HotwireRegistry::make();
    $definitions = [...array_values($registry->components()), ...array_values($registry->controllers())];
    $visualSlots = collect($definitions)
        ->flatMap(fn ($definition): array => $definition->styling->visualSlots())
        ->unique()
        ->values()
        ->all();
    $structuralSlots = collect($definitions)
        ->flatMap(fn ($definition): array => $definition->styling->structuralSlots())
        ->unique()
        ->values();
    preg_match_all('/^\s*\[data-slot="([a-z0-9-]+)"\] \{\}$/m', $css, $rules);

    expect(File::exists($path))->toBeTrue()
        ->and($css)->toContain('@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";')
        ->and($css)->toContain('@import "../../../vendor/emaia/laravel-hotwire/resources/css/custom-variants.css";')
        ->and($css)->toContain('@import "../../../vendor/emaia/laravel-hotwire/resources/css/structural.css";')
        ->and($css)->toContain('/* Accordion */')
        ->and($css)->toContain('/* Tooltip */')
        ->and($rules[1])->toEqualCanonicalizing($visualSlots)
        ->and($css)->toEndWith("\n");

    foreach ($structuralSlots as $slot) {
        expect($css)->not->toContain("[data-slot=\"{$slot}\"]");
    }
});

it('groups authored Tooltip and Toaster anatomy under their components', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    $css = File::get($this->targetDir.'/brand.css');

    expect($css)
        ->toMatch('/\/\* Tooltip \*\/\s+\[data-slot="tooltip"\] \{\}\s+\[data-slot="tooltip-arrow"\] \{\}/')
        ->toMatch('/\/\* Toaster \*\/\s+\[data-slot="toast"\] \{\}\s+\[data-slot="toast-content"\] \{\}\s+\[data-slot="toast-icon"\] \{\}\s+\[data-slot="toast-body"\] \{\}\s+\[data-slot="toast-title"\] \{\}\s+\[data-slot="toast-description"\] \{\}\s+\[data-slot="toast-close"\] \{\}/')
        ->not->toContain('/* Tooltip controller */')
        ->not->toContain('/* Toaster controller */')
        ->not->toContain('[data-slot="toaster"]')
        ->not->toContain('[data-slot="toast-trigger"]');
});

it('groups shared slots under their declaring family', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    $css = File::get($this->targetDir.'/brand.css');

    expect($css)
        ->toMatch('/\/\* Sticky \*\/\s+\[data-slot="sticky"\] \{\}/')
        ->not->toMatch('/\/\* Navbar \*\/(?:(?!\/\*).)*\[data-slot="sticky"\]/s')
        ->not->toContain('/* Field Error */')
        ->not->toContain('/* Toggle Group Item */');
});

it('inherits the runtime safelist rather than snapshotting it', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    // Written into the scaffold, the list would freeze at whatever the package safelisted that day.
    expect(File::get($this->targetDir.'/brand.css'))
        ->not->toContain('@source inline(')
        ->toContain('@import "../../../vendor/emaia/laravel-hotwire/resources/css/structural.css";');
});

it('does not copy selector decomposition from a shipped preset', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    $css = File::get($this->targetDir.'/brand.css');

    expect($css)
        ->toContain('[data-slot="accordion-trigger"] {}')
        ->not->toContain('[data-slot="accordion-item"][aria-disabled="true"]')
        ->not->toContain('[data-carousel-axis="y"]')
        ->not->toContain('@media (prefers-reduced-motion: reduce)');
});

it('scaffolds no rule the structural stylesheet owns', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    // Accordion collapse and carousel geometry are mechanics; a scaffolded empty body would read as
    // an invitation to reimplement them, and a preset that skipped it would ship a broken component.
    expect(File::get($this->targetDir.'/brand.css'))
        ->not->toContain('::details-content')
        ->not->toContain('data-carousel-container');
});

it('templates every token declared by the package, in both colour schemes', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    $css = File::get($this->targetDir.'/brand.css');

    expect($css)
        ->toContain('Uncomment and replace these values to override the shared theme tokens.')
        ->toContain(':where(:root:not([data-theme="dark"])),')
        ->toContain('[data-theme="light"] {')
        ->toContain('[data-theme="dark"] {')
        ->toContain('--radius: ...;');

    $tokensCss = File::get(__DIR__.'/../../resources/css/tokens.css');

    // Compare block by block: `--radius` lives only in `:root`, so a whole-file check would either
    // miss omissions or demand a token the package never declares for dark.
    foreach (['/^:root \{(.*?)^\}/ms', '/^\[data-theme="light"\] \{(.*?)^\}/ms', '/^\[data-theme="dark"\] \{(.*?)^\}/ms'] as $block) {
        $declared = blockTokens($block, $tokensCss);
        $generated = blockTokens($block, $css);

        expect($declared)->not->toBeEmpty()
            ->and(array_diff($declared, $generated))
            ->toBe([], "Block [{$block}] of the generated preset omits tokens declared in tokens.css.");
    }
});

it('fails visibly when package token sections cannot be extracted', function () {
    $this->app->instance(Filesystem::class, tokenFilesystem(':root { --radius: 1rem; }'));

    $this->artisan('hotwire:make-preset brand --no-interaction')
        ->expectsOutputToContain('Could not extract root, light, and dark token sections')
        ->assertFailed();

    expect(File::exists($this->targetDir.'/brand.css'))->toBeFalse();
});

it('guards the generated light selector the way the package guards its own', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    // Without `:where()` the generated block outranks the symmetric `[data-theme="light"]` override
    // the theming docs show, so an application override would lose on the root element alone.
    expect(File::get($this->targetDir.'/brand.css'))
        ->toContain(":where(:root:not([data-theme=\"dark\"])),\n[data-theme=\"light\"] {");
});

it('files a dark rule that carries its own negation under dark', function () {
    $this->app->instance(Filesystem::class, tokenFilesystem(<<<'CSS'
        :root { --radius: 1rem; }

        [data-theme="light"] { --background: oklch(1 0 0); }

        [data-theme="dark"]:not([data-theme-flat]) { --background: oklch(0 0 0); }
        CSS));

    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    expect(File::get($this->targetDir.'/brand.css'))
        ->toContain("[data-theme=\"dark\"] {\n    --background: oklch(...);");
});

it('files a dark rule that excludes a light island under dark', function () {
    $this->app->instance(Filesystem::class, tokenFilesystem(<<<'CSS'
        :root { --radius: 1rem; }

        [data-theme="light"] { --background: oklch(1 0 0); }

        [data-theme="dark"]:not([data-theme="light"] *) { --background: oklch(0 0 0); }
        CSS));

    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    expect(File::get($this->targetDir.'/brand.css'))
        ->toContain("[data-theme=\"dark\"] {\n    --background: oklch(...);");
});

it('extracts token sections independently of selector formatting and order', function () {
    $this->app->instance(Filesystem::class, tokenFilesystem(<<<'CSS'
        :root { --radius: 1rem; }

        [data-context="preview"], [data-theme = 'light'],
        :where(:root:not([data-theme = "dark"])) { --background: oklch(1 0 0); }

        [data-theme = dark] { --background: oklch(0 0 0); }
        CSS));

    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    expect(File::get($this->targetDir.'/brand.css'))
        ->toContain(":where(:root:not([data-theme=\"dark\"])),\n[data-theme=\"light\"]")
        ->toContain('[data-theme="dark"]');
});

it('templates custom properties whose names carry digits or underscores', function () {
    $this->app->instance(Filesystem::class, tokenFilesystem(<<<'CSS'
        :root { --radius: 1rem; }

        [data-theme="light"] { --background: oklch(1 0 0); --chart-1: oklch(0.6 0.2 40); --brand_2: oklch(0.5 0 0); }

        [data-theme="dark"] { --background: oklch(0 0 0); --chart-1: oklch(0.7 0.2 40); --brand_2: oklch(0.8 0 0); }
        CSS));

    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    expect(File::get($this->targetDir.'/brand.css'))
        ->toContain('--chart-1: oklch(...);')
        ->toContain('--brand_2: oklch(...);');
});

it('templates a declaration shared by the light and dark selectors into both', function () {
    $this->app->instance(Filesystem::class, tokenFilesystem(<<<'CSS'
        :root { --radius: 1rem; }

        [data-theme="light"] { --background: oklch(1 0 0); }

        [data-theme="dark"] { --background: oklch(0 0 0); }

        [data-theme="light"], [data-theme="dark"] { --shared: oklch(0.5 0 0); }
        CSS));

    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    $css = File::get($this->targetDir.'/brand.css');

    expect(blockTokens('/^:where\(:root:not\(\[data-theme="dark"\]\)\),\n\[data-theme="light"\] \{(.*?)^\}/ms', $css))
        ->toContain('--shared')
        ->and(blockTokens('/^\[data-theme="dark"\] \{(.*?)^\}/ms', $css))
        ->toContain('--shared');
});

/** @return string[] */
function blockTokens(string $blockPattern, string $css): array
{
    preg_match($blockPattern, $css, $match);
    preg_match_all('/^\s*(--[a-z0-9_-]+):/m', $match[1] ?? '', $tokens);

    return $tokens[1];
}

function tokenFilesystem(string $tokens): Filesystem
{
    return new class(dirname(__DIR__, 2).'/resources/css/tokens.css', $tokens) extends Filesystem
    {
        public function __construct(
            private readonly string $tokensPath,
            private readonly string $tokens,
        ) {}

        public function get($path, $lock = false)
        {
            return $path === $this->tokensPath ? $this->tokens : parent::get($path, $lock);
        }
    };
}

it('clones a shipped preset with package imports and flattened visual sources', function () {
    $this->artisan('hotwire:make-preset brand --from=nova --no-interaction')
        ->assertSuccessful();

    $source = app(CssPresetFiles::class)->source('nova');
    $expected = implode("\n", [
        '@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";',
        '@import "../../../vendor/emaia/laravel-hotwire/resources/css/custom-variants.css";',
        '@import "../../../vendor/emaia/laravel-hotwire/resources/css/structural.css";',
        '',
        $source->visualCss(),
        '',
    ]);

    expect(File::get($this->targetDir.'/brand.css'))->toBe($expected);
});

it('clones a synthetic preset without preserving its private source organization', function () {
    $presets = syntheticCssPresetFiles();
    $this->app->instance(CssPresetFiles::class, $presets);

    foreach ($presets->names() as $preset) {
        $target = "brand-{$preset}";
        $this->artisan("hotwire:make-preset {$target} --from={$preset} --no-interaction")
            ->assertSuccessful();

        expect(File::get($this->targetDir."/{$target}.css"))
            ->toStartWith('@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";')
            ->toContain('@import "../../../vendor/emaia/laravel-hotwire/resources/css/structural.css";')
            ->not->toContain("@import \"./{$preset}/");
    }

    $constellation = File::get($this->targetDir.'/brand-constellation.css');

    expect($constellation)
        ->toContain('@import "../../../vendor/emaia/laravel-hotwire/resources/css/foundations/metrics.css";')
        ->toContain(':where([data-slot="panel"], [data-slot="action"])')
        ->toContain('[data-state="busy"] [data-slot="status"]')
        ->not->toContain('layout/surfaces.css')
        ->and(strpos($constellation, '[data-slot="panel"]'))
        ->toBeLessThan(strpos($constellation, '[data-state="busy"]'))
        ->and(File::get($this->targetDir.'/brand-orbit.css'))
        ->toContain(':where([data-slot="action"], [data-slot="status"])');
});

it('does not modify the application css entrypoint', function () {
    File::ensureDirectoryExists(resource_path('css'));
    File::put(resource_path('css/app.css'), '/* app-owned */');

    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    expect(File::get(resource_path('css/app.css')))->toBe('/* app-owned */');
});

it('rejects invalid preset names', function (string $name) {
    $this->artisan("hotwire:make-preset {$name} --no-interaction")
        ->assertFailed();

    expect(File::isDirectory($this->targetDir))->toBeFalse();
})->with([
    'uppercase' => 'Brand',
    'underscore' => 'brand_theme',
    'leading number' => '2brand',
    'path traversal' => '../brand',
    'extension' => 'brand.css',
]);

it('refuses to overwrite an existing preset without force', function () {
    File::ensureDirectoryExists($this->targetDir);
    File::put($this->targetDir.'/brand.css', '/* custom */');

    $this->artisan('hotwire:make-preset brand --no-interaction')->assertFailed();

    expect(File::get($this->targetDir.'/brand.css'))->toBe('/* custom */');
});

it('overwrites an existing preset with force', function () {
    File::ensureDirectoryExists($this->targetDir);
    File::put($this->targetDir.'/brand.css', '/* custom */');

    $this->artisan('hotwire:make-preset brand --force --no-interaction')->assertSuccessful();

    expect(File::get($this->targetDir.'/brand.css'))
        ->toContain('@layer components')
        ->not->toContain('/* custom */');
});

it('validates the source before overwriting a preset', function () {
    File::ensureDirectoryExists($this->targetDir);
    File::put($this->targetDir.'/brand.css', '/* custom */');

    $this->artisan('hotwire:make-preset brand --from=missing --force --no-interaction')
        ->assertFailed();

    expect(File::get($this->targetDir.'/brand.css'))->toBe('/* custom */');
});

it('prints the generated path and import hint', function () {
    $this->artisan('hotwire:make-preset high-contrast --no-interaction')
        ->expectsOutputToContain('resources/css/presets/high-contrast.css')
        ->expectsOutputToContain("@import './presets/high-contrast.css';")
        ->assertSuccessful();
});
