<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\CssModuleManifest;
use Emaia\LaravelHotwire\Support\PresetSourceException;

it('closes dependencies while preserving canonical preset source order', function () {
    $manifest = CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [
            'button-surfaces' => [
                'components' => ['button'],
                'dependencies' => [],
            ],
            'modal' => [
                'components' => ['modal'],
                'dependencies' => ['button-surfaces', 'overlay-foundation'],
            ],
            'overlay-foundation' => [
                'components' => [],
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
    ]));

    $modules = $manifest->modulesFor(['modal']);

    expect($modules)->toEqualCanonicalizing(['modal', 'button-surfaces', 'overlay-foundation'])
        ->and($manifest->sourcesFor('nova', $modules))->toBe([
            'presets/nova/theme.css',
            'presets/nova/modal.css',
            'presets/nova/button-surfaces.css',
            'presets/nova/overlay-foundation.css',
        ]);
});

it('selects only component-owned visual modules and their dependencies', function () {
    $manifest = CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [
            'floating-presence' => [
                'components' => ['tooltip'],
                'dependencies' => [],
            ],
            'tooltip' => [
                'components' => ['tooltip'],
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
    ]));

    expect($manifest->modulesFor(['tooltip']))
        ->toEqualCanonicalizing(['floating-presence', 'tooltip']);
});

it('rejects legacy controller ownership in visual modules', function () {
    CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [
            'tooltip' => [
                'components' => [],
                'controllers' => ['tooltip'],
                'dependencies' => [],
            ],
        ],
        'presets' => ['nova' => ['base' => [], 'sources' => []]],
    ]));
})->throws(PresetSourceException::class, 'CSS module [tooltip] may only define components and dependencies.');

it('resolves a synthetic preset without official name or source organization assumptions', function () {
    $manifest = CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [
            'surface' => [
                'components' => ['card'],
                'dependencies' => [],
            ],
            'action' => [
                'components' => ['button'],
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
    ]));

    expect($manifest->sourcesFor('contrast-fixture', $manifest->modulesFor(['button'])))
        ->toBe(['presets/contrast-fixture/layout/surfaces.css']);
});

it('includes preset base before modules even when the module closure is empty', function () {
    $manifest = CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [
            'surface' => [
                'components' => ['card'],
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
    ]));

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

it('inherits foundation tokens while exposing explicit preset additions', function () {
    $manifest = CssModuleManifest::fromArray(tokenManifestDefinition());

    expect($manifest->presetNames())->toBe(['contrast-fixture'])
        ->and($manifest->foundationPropertyScopes())->toBe([
            '--background' => 'themed',
            '--foreground' => 'themed',
            '--radius' => 'global',
        ])
        ->and($manifest->additionalPropertyScopesFor('contrast-fixture'))->toBe([
            '--status' => 'themed',
            '--status-foreground' => 'themed',
            '--radius-action' => 'global',
        ])
        ->and($manifest->additionalPropertiesFor('contrast-fixture'))->toBe([
            '--status',
            '--status-foreground',
            '--radius-action',
        ])
        ->and($manifest->propertiesFor('contrast-fixture'))->toBe([
            '--background',
            '--foreground',
            '--radius',
            '--status',
            '--status-foreground',
            '--radius-action',
        ])
        ->and($manifest->aliasesFor('contrast-fixture'))->toBe([
            '--color-background' => '--background',
            '--color-foreground' => '--foreground',
            '--radius-md' => '--radius',
            '--color-status' => '--status',
        ])
        ->and($manifest->contrastPairsFor('contrast-fixture'))->toBe([
            'background' => [
                'foreground' => '--foreground',
                'background' => '--background',
            ],
            'status' => [
                'foreground' => '--status-foreground',
                'background' => '--status',
            ],
        ]);
});

it('requires explicit token metadata for the foundation and every preset', function (array $manifest, string $message) {
    expect(fn () => CssModuleManifest::fromArray($manifest))
        ->toThrow(PresetSourceException::class, $message);
})->with([
    'missing foundation' => [
        [
            'modules' => [],
            'presets' => [],
        ],
        'CSS foundation token metadata must define properties, aliases, and contrast_pairs.',
    ],
    'missing preset metadata' => [
        [
            'foundation' => emptyTokenMetadata(),
            'modules' => [],
            'presets' => ['nova' => ['base' => [], 'sources' => []]],
        ],
        'CSS module preset [nova] token metadata must define properties, aliases, and contrast_pairs.',
    ],
]);

it('accepts contrast pair keys in either order and normalizes their shape', function () {
    $definition = tokenManifestDefinition();
    $definition['presets']['contrast-fixture']['contrast_pairs']['status'] = [
        'background' => '--status',
        'foreground' => '--status-foreground',
    ];

    expect(CssModuleManifest::fromArray($definition)->contrastPairsFor('contrast-fixture')['status'])
        ->toBe([
            'foreground' => '--status-foreground',
            'background' => '--status',
        ]);
});

it('rejects invalid or ambiguous token metadata', function (Closure $mutate, string $message) {
    $manifest = tokenManifestDefinition();
    $mutate($manifest);

    expect(fn () => CssModuleManifest::fromArray($manifest))
        ->toThrow(PresetSourceException::class, $message);
})->with([
    'property without custom property prefix' => [
        function (array &$manifest): void {
            $manifest['presets']['contrast-fixture']['properties']['status'] = 'themed';
        },
        'CSS module preset [contrast-fixture] properties must contain CSS custom property names beginning with --.',
    ],
    'legacy property list' => [
        function (array &$manifest): void {
            $manifest['presets']['contrast-fixture']['properties'] = ['--status', '--status-foreground'];
        },
        'CSS module preset [contrast-fixture] properties must map each custom property name to global or themed.',
    ],
    'property with invalid identifier punctuation' => [
        function (array &$manifest): void {
            $manifest['presets']['contrast-fixture']['properties']['--status!'] = 'themed';
        },
        'CSS module preset [contrast-fixture] properties must contain CSS custom property names beginning with --.',
    ],
    'unsupported property scope' => [
        function (array &$manifest): void {
            $manifest['presets']['contrast-fixture']['properties']['--status'] = 'optional';
        },
        'CSS module preset [contrast-fixture] property [--status] must use scope global or themed.',
    ],
    'foundation property repeated by preset' => [
        function (array &$manifest): void {
            $manifest['presets']['contrast-fixture']['properties']['--radius'] = 'global';
        },
        'CSS module preset [contrast-fixture] property [--radius] already belongs to the shared foundation.',
    ],
    'invalid alias name' => [
        function (array &$manifest): void {
            $manifest['presets']['contrast-fixture']['aliases']['color-status'] = '--status';
        },
        'CSS module preset [contrast-fixture] alias [color-status] must be a CSS custom property name beginning with --.',
    ],
    'unknown alias target' => [
        function (array &$manifest): void {
            $manifest['presets']['contrast-fixture']['aliases']['--color-missing'] = '--missing';
        },
        'CSS module preset [contrast-fixture] alias [--color-missing] references unknown property [--missing].',
    ],
    'foundation alias repeated by preset' => [
        function (array &$manifest): void {
            $manifest['presets']['contrast-fixture']['aliases']['--radius-md'] = '--radius-action';
        },
        'CSS module preset [contrast-fixture] alias [--radius-md] already belongs to the shared foundation.',
    ],
    'contrast pair with invalid shape' => [
        function (array &$manifest): void {
            $manifest['presets']['contrast-fixture']['contrast_pairs']['notice'] = ['--status-foreground', '--status'];
        },
        'CSS module preset [contrast-fixture] contrast pair [notice] must define foreground and background.',
    ],
    'contrast pair with an extra key' => [
        function (array &$manifest): void {
            $manifest['presets']['contrast-fixture']['contrast_pairs']['notice'] = [
                'background' => '--status',
                'foreground' => '--status-foreground',
                'border' => '--status',
            ];
        },
        'CSS module preset [contrast-fixture] contrast pair [notice] must define foreground and background.',
    ],
    'contrast pair with unknown property' => [
        function (array &$manifest): void {
            $manifest['presets']['contrast-fixture']['contrast_pairs']['notice'] = [
                'foreground' => '--missing',
                'background' => '--status',
            ];
        },
        'CSS module preset [contrast-fixture] contrast pair [notice] references unknown property [--missing].',
    ],
    'foundation pair repeated by preset' => [
        function (array &$manifest): void {
            $manifest['presets']['contrast-fixture']['contrast_pairs']['background'] = [
                'foreground' => '--status-foreground',
                'background' => '--status',
            ];
        },
        'CSS module preset [contrast-fixture] contrast pair [background] already belongs to the shared foundation.',
    ],
]);

it('requires every preset to declare its base explicitly', function () {
    CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [],
        'presets' => ['nova' => ['sources' => []]],
    ]));
})->throws(PresetSourceException::class, 'CSS module manifest contains an invalid preset definition.');

it('requires preset base and module sources to be ordered lists', function (array $definition) {
    CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [],
        'presets' => ['nova' => $definition],
    ]));
})->with([
    'base map' => [['base' => ['theme' => 'presets/nova/theme.css'], 'sources' => []]],
    'sources map' => [['base' => [], 'sources' => ['theme' => ['path' => 'presets/nova/theme.css', 'modules' => []]]]],
])->throws(PresetSourceException::class, 'CSS module manifest contains an invalid preset definition.');

it('requires every package preset to ship its public entrypoint', function () {
    $manifest = CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [],
        'presets' => [
            'missing' => [
                'base' => [],
                'sources' => [],
            ],
        ],
    ]));
    $validate = new ReflectionMethod($manifest, 'validatePackageContract');

    expect(fn () => $validate->invoke($manifest))
        ->toThrow(
            PresetSourceException::class,
            'CSS preset [missing] entrypoint [presets/missing.css] does not exist.',
        );
});

it('rejects unsafe private source paths', function (string $path) {
    CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [
            'surface' => [
                'components' => ['card'],
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
    ]));
})->with([
    'parent traversal' => 'presets/contrast-fixture/layout/../surface.css',
    'hidden segment' => 'presets/contrast-fixture/.private/surface.css',
    'empty segment' => 'presets/contrast-fixture/layout//surface.css',
    'backslash' => 'presets/contrast-fixture/layout\\surface.css',
])->throws(PresetSourceException::class, 'CSS module preset [contrast-fixture] contains an invalid source.');

it('rejects unsafe preset base paths', function (string $path) {
    CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [],
        'presets' => [
            'contrast-fixture' => [
                'base' => [$path],
                'sources' => [],
            ],
        ],
    ]));
})->with([
    'outside preset' => 'foundation.css',
    'other preset' => 'presets/nova/theme.css',
    'parent traversal' => 'presets/contrast-fixture/layout/../theme.css',
    'hidden segment' => 'presets/contrast-fixture/.private/theme.css',
    'empty segment' => 'presets/contrast-fixture/layout//theme.css',
    'backslash' => 'presets/contrast-fixture/layout\\theme.css',
])->throws(PresetSourceException::class, 'CSS module preset [contrast-fixture] contains an invalid base source.');

it('rejects paths repeated between preset base and module sources', function () {
    CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [
            'surface' => ['components' => [], 'dependencies' => []],
        ],
        'presets' => [
            'nova' => [
                'base' => ['presets/nova/theme.css'],
                'sources' => [
                    ['path' => 'presets/nova/theme.css', 'modules' => ['surface']],
                ],
            ],
        ],
    ]));
})->throws(PresetSourceException::class, 'CSS module preset [nova] repeats source [presets/nova/theme.css].');

it('rejects module sources without module ownership', function () {
    CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [],
        'presets' => [
            'nova' => [
                'base' => [],
                'sources' => [
                    ['path' => 'presets/nova/theme.css', 'modules' => []],
                ],
            ],
        ],
    ]));
})->throws(PresetSourceException::class, 'CSS source [presets/nova/theme.css] must map at least one module.');

it('selects Tooltip visuals through package components but not the standalone controller', function () {
    $manifest = app(CssModuleManifest::class);

    expect($manifest->baseFor('nova'))->toBe([])
        ->and($manifest->additionalPropertiesFor('nova'))->toBe([])
        ->and($manifest->additionalAliasesFor('nova'))->toBe([])
        ->and($manifest->additionalContrastPairsFor('nova'))->toBe([])
        ->and($manifest->propertiesFor('nova'))->toBe($manifest->foundationProperties())
        ->and($manifest->aliasesFor('nova'))->toBe($manifest->foundationAliases())
        ->and($manifest->contrastPairsFor('nova'))->toBe($manifest->foundationContrastPairs())
        ->and($manifest->modulesFor(['tooltip']))->toContain('floating-presence', 'kbd', 'tooltip')
        ->and($manifest->modulesFor(['button']))->toContain('tooltip')
        ->and($manifest->modulesFor(['color-scheme.toggle']))->toContain('tooltip')
        ->and($manifest->modulesFor(['sidebar']))->toContain('sidebar', 'tooltip')
        ->and($manifest->modulesFor([]))->toBe([]);
});

it('selects Toaster visuals through the package component but not the standalone controller', function () {
    $manifest = app(CssModuleManifest::class);

    expect($manifest->modulesFor(['toaster']))->toContain('toaster')
        ->and($manifest->modulesFor([]))->toBe([]);
});

it('selects Video Embed visuals through the package component but not the standalone OEmbed controller', function () {
    $manifest = app(CssModuleManifest::class);

    expect($manifest->modulesFor(['video-embed']))->toContain('video-embed')
        ->and($manifest->modulesFor([]))->toBe([]);
});

it('includes upload state styling with the file upload component', function () {
    expect(app(CssModuleManifest::class)->modulesFor(['file-upload']))
        ->toContain('file-upload', 'text-shimmer');
});

it('rejects dependencies on undefined modules', function () {
    CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [
            'modal' => [
                'components' => ['modal'],
                'dependencies' => ['missing'],
            ],
        ],
        'presets' => ['nova' => ['base' => [], 'sources' => []]],
    ]));
})->throws(PresetSourceException::class, 'CSS module [modal] depends on undefined module [missing].');

it('reports the complete module dependency cycle', function () {
    CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [
            'modal' => ['components' => [], 'dependencies' => ['overlay']],
            'overlay' => ['components' => [], 'dependencies' => ['floating']],
            'floating' => ['components' => [], 'dependencies' => ['modal']],
        ],
        'presets' => ['nova' => ['base' => [], 'sources' => []]],
    ]));
})->throws(PresetSourceException::class, 'CSS module dependency cycle: modal -> overlay -> floating -> modal.');

it('rejects presets that omit a declared module source', function () {
    CssModuleManifest::fromArray(withEmptyTokenMetadata([
        'modules' => [
            'button-surfaces' => ['components' => [], 'dependencies' => []],
            'modal' => ['components' => ['modal'], 'dependencies' => ['button-surfaces']],
        ],
        'presets' => [
            'nova' => [
                'base' => [],
                'sources' => [
                    ['path' => 'presets/nova/modal.css', 'modules' => ['modal']],
                ],
            ],
        ],
    ]));
})->throws(PresetSourceException::class, 'CSS module preset [nova] does not map modules: button-surfaces.');

it('covers every catalog owner with visual slots', function () {
    $manifest = app(CssModuleManifest::class);
    $registry = HotwireRegistry::make();

    foreach ($registry->components() as $key => $component) {
        if ($component->styling->visualSlots() !== []) {
            expect($manifest->modulesFor([$key]))->not->toBeEmpty("Component [{$key}] has no CSS module.");
        }
    }
});

/** @return array{properties: array<never>, aliases: array<never>, contrast_pairs: array<never>} */
function emptyTokenMetadata(): array
{
    return [
        'properties' => [],
        'aliases' => [],
        'contrast_pairs' => [],
    ];
}

/** @param array<string, mixed> $manifest @return array<string, mixed> */
function withEmptyTokenMetadata(array $manifest): array
{
    $manifest['foundation'] = emptyTokenMetadata();

    foreach ($manifest['presets'] as &$preset) {
        if (is_array($preset)) {
            $preset += emptyTokenMetadata();
        }
    }

    return $manifest;
}

/** @return array<string, mixed> */
function tokenManifestDefinition(): array
{
    return [
        'foundation' => [
            'properties' => [
                '--background' => 'themed',
                '--foreground' => 'themed',
                '--radius' => 'global',
            ],
            'aliases' => [
                '--color-background' => '--background',
                '--color-foreground' => '--foreground',
                '--radius-md' => '--radius',
            ],
            'contrast_pairs' => [
                'background' => [
                    'foreground' => '--foreground',
                    'background' => '--background',
                ],
            ],
        ],
        'modules' => [],
        'presets' => [
            'contrast-fixture' => [
                'base' => [
                    'presets/contrast-fixture/theme.css',
                    'presets/contrast-fixture/aliases.css',
                ],
                'properties' => [
                    '--status' => 'themed',
                    '--status-foreground' => 'themed',
                    '--radius-action' => 'global',
                ],
                'aliases' => ['--color-status' => '--status'],
                'contrast_pairs' => [
                    'status' => [
                        'foreground' => '--status-foreground',
                        'background' => '--status',
                    ],
                ],
                'sources' => [],
            ],
        ],
    ];
}
