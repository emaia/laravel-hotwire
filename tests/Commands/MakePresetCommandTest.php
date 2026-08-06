<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\PresetAxes;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->appBase = isolateAppPaths();
    $this->targetDir = resource_path('css/presets');
});

afterEach(function () {
    releaseIsolatedAppPaths($this->appBase);
});

it('creates a complete visual slot scaffold grouped by catalog entry', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')
        ->assertSuccessful();

    $path = $this->targetDir.'/brand.css';
    $css = File::get($path);
    $visualSlots = collect([
        ...array_values(HotwireRegistry::make()->components()),
        ...array_values(HotwireRegistry::make()->controllers()),
    ])->flatMap(fn ($definition): array => $definition->styling->visualSlots())->unique();
    $structuralSlots = collect(HotwireRegistry::make()->components())
        ->flatMap(fn ($definition): array => $definition->styling->structuralSlots())
        ->unique();

    expect(File::exists($path))->toBeTrue()
        ->and($css)->toContain('@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";')
        ->and($css)->toContain('@import "../../../vendor/emaia/laravel-hotwire/resources/css/custom-variants.css";')
        ->and($css)->toContain('/* Accordion */')
        ->and($css)->toContain('/* Tooltip controller */')
        ->and($css)->not->toContain('[data-slot="form"]')
        ->and($css)->not->toContain('[data-slot="carousel-viewport"]')
        ->and($css)->toEndWith("\n");

    foreach ($visualSlots as $slot) {
        expect(substr_count($css, "[data-slot=\"{$slot}\"] {}"))
            ->toBe(1, "Visual slot [{$slot}] must be scaffolded exactly once.");
    }

    foreach ($structuralSlots as $slot) {
        expect($css)->not->toContain("[data-slot=\"{$slot}\"]");
    }
});

it('carries the shipped runtime safelist instead of a copy', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    preg_match(
        '/@source inline\("[^"]*"\);/',
        File::get(__DIR__.'/../../resources/css/presets/nova.css'),
        $shipped,
    );

    expect(File::get($this->targetDir.'/brand.css'))->toContain($shipped[0]);
});

it('documents every axis the source preset differentiates, beside the slot that carries it', function () {
    $this->artisan('hotwire:make-preset brand --no-interaction')->assertSuccessful();

    $css = File::get($this->targetDir.'/brand.css');
    $axes = (new PresetAxes)->extract(File::get(__DIR__.'/../../resources/css/presets/nova.css'));
    $missing = [];

    foreach ($axes as $slot => $bySlot) {
        if (! str_contains($css, "[data-slot=\"{$slot}\"] {}")) {
            continue;
        }

        $expected = collect($bySlot)
            ->map(fn (array $values, string $axis): string => "    /* data-{$axis}: ".implode(', ', $values).' */')
            ->push("    [data-slot=\"{$slot}\"] {}")
            ->implode("\n");

        if (! str_contains($css, $expected)) {
            $missing[] = $slot;
        }
    }

    expect($missing)->toBe([], 'Scaffold does not document the axes Nova differentiates for these slots.')
        // Beyond variant and size: the catalog never knew these, the stylesheet always did.
        ->and($css)->toContain('    /* data-orientation: horizontal, vertical */')
        ->and($css)->toContain('    /* data-align: inline-start, inline-end, block-start, block-end */')
        ->and($css)->toContain("    /* Alert Dialog */\n    /* data-state: open */\n    [data-slot=\"alert-dialog-overlay\"] {}");
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
        ['@import "../tokens.css";', '@import "../custom-variants.css";'],
        [
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";',
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/custom-variants.css";',
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
