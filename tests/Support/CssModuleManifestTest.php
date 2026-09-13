<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\CssModuleManifest;
use Emaia\LaravelHotwire\Support\PresetSourceException;

it('closes dependencies while preserving canonical preset source order', function () {
    $manifest = CssModuleManifest::fromArray([
        'modules' => [
            'button-surfaces' => [
                'components' => ['button'],
                'controllers' => [],
                'dependencies' => [],
            ],
            'modal' => [
                'components' => ['modal'],
                'controllers' => ['modal'],
                'dependencies' => ['button-surfaces', 'overlay-foundation'],
            ],
            'overlay-foundation' => [
                'components' => [],
                'controllers' => [],
                'dependencies' => [],
            ],
        ],
        'presets' => [
            'nova' => [
                'base' => ['presets/nova/theme.css'],
                'sources' => [
                    ['path' => 'presets/nova/modal.css', 'modules' => ['modal']],
                    ['path' => 'presets/nova/button-surfaces.css', 'modules' => ['button-surfaces']],
                    ['path' => 'presets/nova/overlay-foundation.css', 'modules' => ['overlay-foundation']],
                ],
            ],
        ],
    ]);

    $modules = $manifest->modulesFor(['modal'], []);

    expect($modules)->toEqualCanonicalizing(['modal', 'button-surfaces', 'overlay-foundation'])
        ->and($manifest->sourcesFor('nova', $modules))->toBe([
            'presets/nova/theme.css',
            'presets/nova/modal.css',
            'presets/nova/button-surfaces.css',
            'presets/nova/overlay-foundation.css',
        ]);
});

it('selects controller-owned visual modules and their dependencies', function () {
    $manifest = CssModuleManifest::fromArray([
        'modules' => [
            'floating-presence' => [
                'components' => [],
                'controllers' => ['tooltip'],
                'dependencies' => [],
            ],
            'tooltip' => [
                'components' => [],
                'controllers' => ['tooltip'],
                'dependencies' => ['floating-presence'],
            ],
        ],
        'presets' => [
            'nova' => [
                'base' => [],
                'sources' => [
                    ['path' => 'presets/nova/floating-presence.css', 'modules' => ['floating-presence']],
                    ['path' => 'presets/nova/tooltip.css', 'modules' => ['tooltip']],
                ],
            ],
        ],
    ]);

    expect($manifest->modulesFor([], ['tooltip']))
        ->toEqualCanonicalizing(['floating-presence', 'tooltip']);
});

it('resolves a synthetic preset without official name or source organization assumptions', function () {
    $manifest = CssModuleManifest::fromArray([
        'modules' => [
            'surface' => [
                'components' => ['card'],
                'controllers' => [],
                'dependencies' => [],
            ],
            'action' => [
                'components' => ['button'],
                'controllers' => [],
                'dependencies' => ['surface'],
            ],
        ],
        'presets' => [
            'contrast-fixture' => [
                'base' => [],
                'sources' => [
                    [
                        'path' => 'presets/contrast-fixture/layout/surfaces.css',
                        'modules' => ['surface', 'action'],
                    ],
                ],
            ],
        ],
    ]);

    expect($manifest->sourcesFor('contrast-fixture', $manifest->modulesFor(['button'], [])))
        ->toBe(['presets/contrast-fixture/layout/surfaces.css']);
});

it('includes preset base before modules even when the module closure is empty', function () {
    $manifest = CssModuleManifest::fromArray([
        'modules' => [
            'surface' => [
                'components' => ['card'],
                'controllers' => [],
                'dependencies' => [],
            ],
        ],
        'presets' => [
            'contrast-fixture' => [
                'base' => [
                    'presets/contrast-fixture/theme.css',
                    'presets/contrast-fixture/aliases.css',
                ],
                'sources' => [
                    ['path' => 'presets/contrast-fixture/surface.css', 'modules' => ['surface']],
                ],
            ],
        ],
    ]);

    expect($manifest->baseFor('contrast-fixture'))->toBe([
        'presets/contrast-fixture/theme.css',
        'presets/contrast-fixture/aliases.css',
    ])
        ->and($manifest->sourcesFor('contrast-fixture', []))->toBe([
            'presets/contrast-fixture/theme.css',
            'presets/contrast-fixture/aliases.css',
        ])
        ->and($manifest->sourcesFor('contrast-fixture', ['surface']))->toBe([
            'presets/contrast-fixture/theme.css',
            'presets/contrast-fixture/aliases.css',
            'presets/contrast-fixture/surface.css',
        ]);
});

it('requires every preset to declare its base explicitly', function () {
    CssModuleManifest::fromArray([
        'modules' => [],
        'presets' => ['nova' => ['sources' => []]],
    ]);
})->throws(PresetSourceException::class, 'CSS module manifest contains an invalid preset definition.');

it('requires preset base and module sources to be ordered lists', function (array $definition) {
    CssModuleManifest::fromArray([
        'modules' => [],
        'presets' => ['nova' => $definition],
    ]);
})->with([
    'base map' => [['base' => ['theme' => 'presets/nova/theme.css'], 'sources' => []]],
    'sources map' => [['base' => [], 'sources' => ['theme' => ['path' => 'presets/nova/theme.css', 'modules' => []]]]],
])->throws(PresetSourceException::class, 'CSS module manifest contains an invalid preset definition.');

it('requires every package preset to ship its public entrypoint', function () {
    $manifest = CssModuleManifest::fromArray([
        'modules' => [],
        'presets' => [
            'missing' => [
                'base' => [],
                'sources' => [],
            ],
        ],
    ]);
    $validate = new ReflectionMethod($manifest, 'validatePackageContract');

    expect(fn () => $validate->invoke($manifest))
        ->toThrow(
            PresetSourceException::class,
            'CSS preset [missing] entrypoint [presets/missing.css] does not exist.',
        );
});

it('rejects unsafe private source paths', function (string $path) {
    CssModuleManifest::fromArray([
        'modules' => [
            'surface' => [
                'components' => ['card'],
                'controllers' => [],
                'dependencies' => [],
            ],
        ],
        'presets' => [
            'contrast-fixture' => [
                'base' => [],
                'sources' => [
                    ['path' => $path, 'modules' => ['surface']],
                ],
            ],
        ],
    ]);
})->with([
    'parent traversal' => 'presets/contrast-fixture/layout/../surface.css',
    'hidden segment' => 'presets/contrast-fixture/.private/surface.css',
    'empty segment' => 'presets/contrast-fixture/layout//surface.css',
    'backslash' => 'presets/contrast-fixture/layout\\surface.css',
])->throws(PresetSourceException::class, 'CSS module preset [contrast-fixture] contains an invalid source.');

it('rejects unsafe preset base paths', function (string $path) {
    CssModuleManifest::fromArray([
        'modules' => [],
        'presets' => [
            'contrast-fixture' => [
                'base' => [$path],
                'sources' => [],
            ],
        ],
    ]);
})->with([
    'outside preset' => 'foundation.css',
    'other preset' => 'presets/nova/theme.css',
    'parent traversal' => 'presets/contrast-fixture/layout/../theme.css',
    'hidden segment' => 'presets/contrast-fixture/.private/theme.css',
    'empty segment' => 'presets/contrast-fixture/layout//theme.css',
    'backslash' => 'presets/contrast-fixture/layout\\theme.css',
])->throws(PresetSourceException::class, 'CSS module preset [contrast-fixture] contains an invalid base source.');

it('rejects paths repeated between preset base and module sources', function () {
    CssModuleManifest::fromArray([
        'modules' => [
            'surface' => ['components' => [], 'controllers' => [], 'dependencies' => []],
        ],
        'presets' => [
            'nova' => [
                'base' => ['presets/nova/theme.css'],
                'sources' => [
                    ['path' => 'presets/nova/theme.css', 'modules' => ['surface']],
                ],
            ],
        ],
    ]);
})->throws(PresetSourceException::class, 'CSS module preset [nova] repeats source [presets/nova/theme.css].');

it('rejects module sources without module ownership', function () {
    CssModuleManifest::fromArray([
        'modules' => [],
        'presets' => [
            'nova' => [
                'base' => [],
                'sources' => [
                    ['path' => 'presets/nova/theme.css', 'modules' => []],
                ],
            ],
        ],
    ]);
})->throws(PresetSourceException::class, 'CSS source [presets/nova/theme.css] must map at least one module.');

it('selects Tooltip visuals through package components but not the standalone controller', function () {
    $manifest = app(CssModuleManifest::class);

    expect($manifest->baseFor('nova'))->toBe([])
        ->and($manifest->modulesFor(['tooltip'], []))->toContain('floating-presence', 'kbd', 'tooltip')
        ->and($manifest->modulesFor(['button'], ['tooltip']))->toContain('tooltip')
        ->and($manifest->modulesFor(['color-scheme.toggle'], ['color-scheme', 'tooltip']))->toContain('tooltip')
        ->and($manifest->modulesFor(['sidebar'], ['sidebar', 'reveal', 'tooltip']))->toContain('sidebar', 'tooltip')
        ->and($manifest->modulesFor([], ['tooltip']))->toBe([]);
});

it('selects Toaster visuals through the package component but not the standalone controller', function () {
    $manifest = app(CssModuleManifest::class);

    expect($manifest->modulesFor(['toaster'], []))->toContain('toaster')
        ->and($manifest->modulesFor([], ['toaster']))->toBe([]);
});

it('includes upload state styling with the file upload component', function () {
    expect(app(CssModuleManifest::class)->modulesFor(['file-upload'], []))
        ->toContain('file-upload', 'text-shimmer');
});

it('rejects dependencies on undefined modules', function () {
    CssModuleManifest::fromArray([
        'modules' => [
            'modal' => [
                'components' => ['modal'],
                'controllers' => [],
                'dependencies' => ['missing'],
            ],
        ],
        'presets' => ['nova' => ['base' => [], 'sources' => []]],
    ]);
})->throws(PresetSourceException::class, 'CSS module [modal] depends on undefined module [missing].');

it('reports the complete module dependency cycle', function () {
    CssModuleManifest::fromArray([
        'modules' => [
            'modal' => ['components' => [], 'controllers' => [], 'dependencies' => ['overlay']],
            'overlay' => ['components' => [], 'controllers' => [], 'dependencies' => ['floating']],
            'floating' => ['components' => [], 'controllers' => [], 'dependencies' => ['modal']],
        ],
        'presets' => ['nova' => ['base' => [], 'sources' => []]],
    ]);
})->throws(PresetSourceException::class, 'CSS module dependency cycle: modal -> overlay -> floating -> modal.');

it('rejects presets that omit a declared module source', function () {
    CssModuleManifest::fromArray([
        'modules' => [
            'button-surfaces' => ['components' => [], 'controllers' => [], 'dependencies' => []],
            'modal' => ['components' => ['modal'], 'controllers' => [], 'dependencies' => ['button-surfaces']],
        ],
        'presets' => [
            'nova' => [
                'base' => [],
                'sources' => [
                    ['path' => 'presets/nova/modal.css', 'modules' => ['modal']],
                ],
            ],
        ],
    ]);
})->throws(PresetSourceException::class, 'CSS module preset [nova] does not map modules: button-surfaces.');

it('covers every catalog owner with visual slots', function () {
    $manifest = app(CssModuleManifest::class);
    $registry = HotwireRegistry::make();

    foreach ($registry->components() as $key => $component) {
        if ($component->styling->visualSlots() !== []) {
            expect($manifest->modulesFor([$key], []))->not->toBeEmpty("Component [{$key}] has no CSS module.");
        }
    }

    foreach ($registry->controllers() as $key => $controller) {
        if ($controller->styling->visualSlots() !== []) {
            expect($manifest->modulesFor([], [$key]))->not->toBeEmpty("Controller [{$key}] has no CSS module.");
        }
    }
});
