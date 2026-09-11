<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\CssModuleManifest;
use Emaia\LaravelHotwire\Support\CssPresetFiles;
use Illuminate\Filesystem\Filesystem;

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

it('discovers and resolves public entrypoints from the configured css root', function () {
    $presets = syntheticCssPresetFiles();
    $source = $presets->source('constellation');

    expect($presets->all())->toBe([
        'constellation' => realpath(__DIR__.'/../Fixtures/css/preset-package/presets/constellation.css'),
        'orbit' => realpath(__DIR__.'/../Fixtures/css/preset-package/presets/orbit.css'),
    ])
        ->and($presets->names())->toBe(['constellation', 'orbit'])
        ->and($source?->foundationImports())->toBe([
            'tokens.css',
            'custom-variants.css',
            'foundations/metrics.css',
            'structural.css',
        ])
        ->and($source?->visualStylesheetPaths())->toBe([
            'presets/constellation/layout/surfaces.css',
            'presets/constellation/feedback.css',
        ])
        ->and($presets->source('orbit')?->visualStylesheetPaths())->toBe(['presets/orbit/all.css']);
});

it('selects synthetic sources independently of their grouping and nesting', function () {
    $presets = syntheticCssPresetFiles();
    $grouped = $presets->sourceForSelection('orbit', ['action']);

    expect($presets->sourceForSelection('constellation', ['action'])?->visualStylesheetPaths())
        ->toBe(['presets/constellation/layout/surfaces.css'])
        ->and($presets->sourceForSelection('constellation', controllers: ['status'])?->visualStylesheetPaths())
        ->toBe(['presets/constellation/feedback.css'])
        ->and($presets->sourceForSelection('constellation', ['action'], ['status'])?->visualStylesheetPaths())
        ->toBe([
            'presets/constellation/layout/surfaces.css',
            'presets/constellation/feedback.css',
        ])
        ->and($grouped?->visualStylesheetPaths())->toBe(['presets/orbit/all.css'])
        ->and($grouped?->visualCss())->toContain('[data-slot="status"]');
});

it('resolves every private source once without exposing its organization as presets', function (string $preset) {
    $presets = app(CssPresetFiles::class);
    $source = $presets->source($preset);
    $cssRoot = dirname($presets->path($preset), 2);
    $privateDirectory = dirname($presets->path($preset))."/{$preset}";
    $sourcePaths = $source->visualStylesheetPaths();
    $resolvedSources = array_map(
        fn (string $path): string => realpath($cssRoot.'/'.$path) ?: $cssRoot.'/'.$path,
        $sourcePaths,
    );
    $privateSources = array_map(
        fn (SplFileInfo $file): string => $file->getRealPath() ?: $file->getPathname(),
        (new Filesystem)->allFiles($privateDirectory),
    );
    $foundations = $source->foundationImports();
    sort($resolvedSources);
    sort($privateSources);

    expect($resolvedSources)->toBe($privateSources)
        ->and($sourcePaths)->toHaveCount(count(array_unique($sourcePaths)))
        ->not->toBeEmpty()
        ->each->toStartWith("presets/{$preset}/")
        ->and($resolvedSources)->each->toBeFile()
        ->and($foundations)->toHaveCount(count(array_unique($foundations)))
        ->and(array_values(array_intersect($foundations, [
            'tokens.css',
            'custom-variants.css',
            'structural.css',
        ])))->toBe([
            'tokens.css',
            'custom-variants.css',
            'structural.css',
        ])
        ->and(file_get_contents($presets->path($preset)))
        ->not->toContain('[data-slot=')
        ->and(array_intersect(
            array_values($presets->all()),
            $resolvedSources,
        ))->toBe([]);
})->with('shipped css preset names');

it('uses responsibility-oriented Nova modules instead of mechanical source chunks', function () {
    $modules = collect((new Filesystem)->allFiles(__DIR__.'/../../resources/css/presets/nova'))
        ->map(fn (SplFileInfo $file): string => $file->getFilename())
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
