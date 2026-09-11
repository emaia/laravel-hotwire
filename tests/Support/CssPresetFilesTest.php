<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\CssModuleManifest;
use Emaia\LaravelHotwire\Support\CssPresetFiles;

dataset('shipped css preset names', fn () => collect(glob(__DIR__.'/../../resources/css/presets/*.css') ?: [])
    ->mapWithKeys(fn (string $path): array => [pathinfo($path, PATHINFO_FILENAME) => [pathinfo($path, PATHINFO_FILENAME)]])
    ->all());

it('discovers shipped css presets in sorted order', function () {
    $presets = app(CssPresetFiles::class);
    $expected = collect(glob(__DIR__.'/../../resources/css/presets/*.css') ?: [])
        ->mapWithKeys(fn (string $path): array => [pathinfo($path, PATHINFO_FILENAME) => realpath($path)])
        ->sortKeys()
        ->all();

    expect($presets->all())->toBe($expected)
        ->and($presets->names())->toBe(array_keys($expected))
        ->and($presets->path('nova'))->toBe($expected['nova'])
        ->and($presets->source('nova')?->visualCss())->toContain('[data-slot="button"]')
        ->and($presets->path('missing'))->toBeNull();
});

it('resolves every private module exactly once without exposing it as a preset', function (string $preset) {
    $presets = app(CssPresetFiles::class);
    $source = $presets->source($preset);
    $modules = glob(dirname($presets->path($preset))."/{$preset}/*.css") ?: [];

    expect($modules)->toHaveCount(count($source->visualStylesheets()))
        ->not->toBeEmpty()
        ->and($source->foundationImports())->toBe([
            'tokens.css',
            'custom-variants.css',
            'structural.css',
        ])
        ->and(file_get_contents($presets->path($preset)))
        ->not->toContain('[data-slot=')
        ->and(array_intersect(
            array_values($presets->all()),
            array_map(fn (string $path): string => realpath($path) ?: $path, $modules),
        ))->toBe([]);
})->with('shipped css preset names');

it('uses responsibility-oriented Nova modules instead of mechanical source chunks', function () {
    $modules = collect(glob(__DIR__.'/../../resources/css/presets/nova/*.css') ?: [])
        ->map(fn (string $path): string => basename($path))
        ->values();

    expect($modules)
        ->toContain(
            'accordion.css',
            'button-surfaces.css',
            'floating-presence.css',
            'checkable-controls.css',
            'sidebar.css',
            'tooltip.css',
        )
        ->each->not->toMatch('/^\d+-/');
});

it('resolves complete and selective preset sources from catalog owners', function (string $preset) {
    $presets = app(CssPresetFiles::class);
    $registry = HotwireRegistry::make();
    $components = array_keys(array_filter(
        $registry->components(),
        fn ($component): bool => $component->styling->visualSlots() !== [],
    ));
    $controllers = array_keys(array_filter(
        $registry->controllers(),
        fn ($controller): bool => $controller->styling->visualSlots() !== [],
    ));
    $modules = app(CssModuleManifest::class)->modulesFor($components, $controllers);

    expect(app(CssModuleManifest::class)->sourcesFor($preset, $modules))
        ->toBe($presets->source($preset)->visualStylesheetPaths())
        ->and($presets->sourceForSelection($preset, $components, $controllers)->visualStylesheets())
        ->toBe($presets->source($preset)->visualStylesheets());
})->with('shipped css preset names');

it('resolves Nova modal integrations without unrelated sources', function () {
    $modal = app(CssPresetFiles::class)->sourceForSelection('nova', ['modal']);

    expect($modal->visualCss())
        ->toContain('[data-slot="modal-panel"]')
        ->toContain('[data-slot="modal-trigger"]')
        ->toContain('[data-slot="drawer-overlay"]')
        ->not->toContain('[data-slot="carousel"]');
});

it('resolves migrated integrations to exact canonical visual sources', function (array $components, array $controllers, array $expected) {
    expect(app(CssPresetFiles::class)->sourceForSelection('nova', $components, $controllers)?->visualStylesheetPaths())
        ->toBe($expected);
})->with([
    'Button with Tooltip' => [
        ['button'],
        [],
        [
            'presets/nova/button-surfaces.css',
            'presets/nova/floating-presence.css',
            'presets/nova/kbd.css',
            'presets/nova/tooltip.css',
        ],
    ],
    'Color Scheme Toggle with Tooltip' => [
        ['color-scheme.toggle'],
        [],
        [
            'presets/nova/button-surfaces.css',
            'presets/nova/color-scheme-toggle.css',
            'presets/nova/floating-presence.css',
            'presets/nova/kbd.css',
            'presets/nova/tooltip.css',
        ],
    ],
    'Toaster component anatomy' => [
        ['toaster'],
        [],
        ['presets/nova/toaster.css'],
    ],
    'OEmbed controller anatomy' => [
        [],
        ['oembed'],
        ['presets/nova/oembed.css'],
    ],
]);
