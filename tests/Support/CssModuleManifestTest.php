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

it('rejects dependencies on undefined modules', function () {
    CssModuleManifest::fromArray([
        'modules' => [
            'modal' => [
                'components' => ['modal'],
                'controllers' => [],
                'dependencies' => ['missing'],
            ],
        ],
        'presets' => ['nova' => ['sources' => []]],
    ]);
})->throws(PresetSourceException::class, 'CSS module [modal] depends on undefined module [missing].');

it('reports the complete module dependency cycle', function () {
    CssModuleManifest::fromArray([
        'modules' => [
            'modal' => ['components' => [], 'controllers' => [], 'dependencies' => ['overlay']],
            'overlay' => ['components' => [], 'controllers' => [], 'dependencies' => ['floating']],
            'floating' => ['components' => [], 'controllers' => [], 'dependencies' => ['modal']],
        ],
        'presets' => ['nova' => ['sources' => []]],
    ]);
})->throws(PresetSourceException::class, 'CSS module dependency cycle: modal -> overlay -> floating -> modal.');

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
