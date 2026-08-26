<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\CssModuleManifest;
use Emaia\LaravelHotwire\Support\CssPresetFiles;

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

it('resolves every private Nova module exactly once without exposing it as a preset', function () {
    $presets = app(CssPresetFiles::class);
    $source = $presets->source('nova');
    $modules = glob(dirname($presets->path('nova')).'/nova/*.css') ?: [];

    expect($modules)->toHaveCount(count($source->visualStylesheets()))
        ->not->toBeEmpty()
        ->and($source->foundationImports())->toBe([
            'tokens.css',
            'custom-variants.css',
            'structural.css',
        ])
        ->and(file_get_contents($presets->path('nova')))
        ->not->toContain('[data-slot=')
        ->and($presets->names())->toBe(['nova']);
});

it('uses responsibility-oriented Nova modules instead of mechanical source chunks', function () {
    $modules = collect(glob(__DIR__.'/../../resources/css/presets/nova/*.css') ?: [])
        ->map(fn (string $path): string => basename($path))
        ->values();

    expect($modules)
        ->toContain(
            'accordion.css',
            'button-surfaces.css',
            'floating-presence.css',
            'overlay-foundation.css',
            'checkable-controls.css',
            'sidebar.css',
            'tooltip.css',
        )
        ->each->not->toMatch('/^\d+-/');
});

it('resolves complete and selective preset sources from catalog owners', function () {
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

    expect($presets->sourceForSelection('nova', $components, $controllers)->visualStylesheets())
        ->toBe($presets->source('nova')->visualStylesheets())
        ->and(app(CssModuleManifest::class)->sourcesFor('nova', $modules))
        ->toBe($presets->source('nova')->visualStylesheetPaths());

    $modal = $presets->sourceForSelection('nova', ['modal']);

    expect($modal->visualCss())
        ->toContain('[data-slot="modal-panel"]')
        ->toContain('[data-slot="modal-trigger"]')
        ->toContain('[data-slot="drawer-overlay"]')
        ->not->toContain('[data-slot="carousel"]');
});
