<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\CssRules;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->appBase = isolateAppPaths();
    $this->targetDir = resource_path('css/presets');
});

afterEach(function () {
    releaseIsolatedAppPaths($this->appBase);
});

it('mirrors every rule the shipped presets define, grouped by catalog entry', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    $path = $this->targetDir.'/brand.css';
    $css = File::get($path);
    $structuralSlots = collect(HotwireRegistry::make()->components())
        ->flatMap(fn ($definition): array => $definition->styling->structuralSlots())
        ->unique();

    expect(File::exists($path))->toBeTrue()
        ->and($css)->toContain('@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";')
        ->and($css)->toContain('@import "../../../vendor/emaia/laravel-hotwire/resources/css/custom-variants.css";')
        ->and($css)->toContain('@import "../../../vendor/emaia/laravel-hotwire/resources/css/structural.css";')
        ->and($css)->toContain('/* Accordion */')
        ->and($css)->toContain('/* Tooltip controller */')
        ->and($css)->toEndWith("\n");

    foreach ($structuralSlots as $slot) {
        expect($css)->not->toContain("[data-slot=\"{$slot}\"]");
    }

    $omitted = array_values(array_filter(
        sourceSelectors(),
        fn (string $selector): bool => ! str_contains($css, "{$selector} {}"),
    ));

    expect($omitted)->toBe([], 'Scaffold omits rules Nova defines.');
});

it('scaffolds no rule the shipped presets do not define', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    $shipped = array_flip(sourceSelectors());
    $extra = [];

    preg_match_all('/^\s*(\S.*?) \{\}$/m', File::get($this->targetDir.'/brand.css'), $matches);

    foreach ($matches[1] as $selector) {
        if (! isset($shipped[$selector])) {
            $extra[] = $selector;
        }
    }

    expect($extra)->toBe([]);
});

/**
 * Every selector Nova defines, normalised the way the scaffold writes them.
 *
 * @return string[]
 */
function sourceSelectors(): array
{
    $rules = new CssRules;
    $css = File::get(__DIR__.'/../../resources/css/presets/nova.css');
    $selectors = [];

    foreach ($rules->parse($rules->stripComments($css)) as ['chain' => $chain]) {
        if (array_filter($chain, fn (string $block): bool => str_starts_with($block, '@keyframes')) !== []) {
            continue;
        }

        $selectors[] = (string) end($chain);
    }

    return array_values(array_unique($selectors));
}

it('inherits the runtime safelist rather than snapshotting it', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    // Written into the scaffold, the list would freeze at whatever the package safelisted that day.
    expect(File::get($this->targetDir.'/brand.css'))
        ->not->toContain('@source inline(')
        ->toContain('@import "../../../vendor/emaia/laravel-hotwire/resources/css/structural.css";');
});

it('scaffolds the compound selector a state needs, not a summary of it', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    $css = File::get($this->targetDir.'/brand.css');

    expect($css)
        ->toContain('[data-slot="accordion-item"][aria-disabled="true"] > [data-slot="accordion-trigger"] {}')
        ->toContain('[data-slot="accordion-item"][open] > [data-slot="accordion-trigger"] [data-slot="accordion-trigger-icon"] {}')
        ->toContain('[data-slot="carousel"][data-carousel-axis="y"] > [data-slot="carousel-prev-button"] {}')
        // The state lives in the selector now; no comment restates it.
        ->not->toContain('/* data-variant')
        ->not->toContain('/* under [data-slot=');
});

it('keeps a rule inside the at-rules that qualify it', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    expect(File::get($this->targetDir.'/brand.css'))->toContain(<<<'CSS'
        @media (prefers-reduced-motion: reduce) {
                :is([data-slot="dropdown-menu"], [data-slot="tooltip"], [data-slot="hover-card-content"], [data-slot="popover-content"], [data-slot="multi-select-content"]) {}
            }
        CSS);
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
        ->toContain('[data-theme="dark"] {')
        ->toContain('--radius: ...;');

    $tokensCss = File::get(__DIR__.'/../../resources/css/tokens.css');

    // Compare block by block: `--radius` lives only in `:root`, so a whole-file check would either
    // miss omissions or demand a token the package never declares for dark.
    foreach (['/^:root \{(.*?)^\}/ms', '/^\[data-theme="dark"\] \{(.*?)^\}/ms'] as $block) {
        $declared = blockTokens($block, $tokensCss);
        $generated = blockTokens($block, $css);

        expect($declared)->not->toBeEmpty()
            ->and(array_diff($declared, $generated))
            ->toBe([], "Block [{$block}] of the generated preset omits tokens declared in tokens.css.");
    }
});

/** @return string[] */
function blockTokens(string $blockPattern, string $css): array
{
    preg_match($blockPattern, $css, $match);
    preg_match_all('/^\s*(--[a-z-]+):/m', $match[1] ?? '', $tokens);

    return $tokens[1];
}

it('clones a shipped preset and rewrites package imports', function () {
    $this->artisan('hotwire:make-preset brand --from=nova --no-interaction')
        ->assertSuccessful();

    $expected = str_replace(
        ['@import "../tokens.css";', '@import "../custom-variants.css";', '@import "../structural.css";'],
        [
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";',
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/custom-variants.css";',
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/structural.css";',
        ],
        File::get(__DIR__.'/../../resources/css/presets/nova.css'),
    );

    expect(File::get($this->targetDir.'/brand.css'))->toBe($expected);
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

    expect(File::get($this->targetDir.'/brand.css'))->toContain('@layer components');
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
