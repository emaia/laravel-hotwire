<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\CssModuleManifest;
use Emaia\LaravelHotwire\Support\CssPresetFiles;
use Emaia\LaravelHotwire\Support\PresetSourceException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
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
        ->and($source?->foundationImports())->toBe(['foundation.css'])
        ->and($source?->visualStylesheetPaths())->toBe([
            'presets/constellation/theme.css',
            'presets/constellation/aliases.css',
            'presets/constellation/layout/surfaces.css',
            'presets/constellation/feedback.css',
        ])
        ->and($source?->baseStylesheetPaths())->toBe([
            'presets/constellation/theme.css',
            'presets/constellation/aliases.css',
        ])
        ->and($source?->moduleStylesheetPaths())->toBe([
            'presets/constellation/layout/surfaces.css',
            'presets/constellation/feedback.css',
        ])
        ->and($source?->baseCss())->toContain('--fixture-radius')
        ->and($source?->baseCss())->toContain('--color-fixture-surface')
        ->and($source?->moduleCss())->not->toContain('--fixture-radius')
        ->and($presets->source('orbit')?->visualStylesheetPaths())->toBe(['presets/orbit/all.css']);
});

it('selects synthetic sources independently of their grouping and nesting', function () {
    $presets = syntheticCssPresetFiles();
    $grouped = $presets->sourceForSelection('orbit', ['action']);

    expect($presets->sourceForSelection('constellation', ['action'])?->visualStylesheetPaths())
        ->toBe([
            'presets/constellation/theme.css',
            'presets/constellation/aliases.css',
            'presets/constellation/layout/surfaces.css',
        ])
        ->and($presets->sourceForSelection('constellation', controllers: ['status'])?->visualStylesheetPaths())
        ->toBe([
            'presets/constellation/theme.css',
            'presets/constellation/aliases.css',
            'presets/constellation/feedback.css',
        ])
        ->and($presets->sourceForSelection('constellation', ['action'], ['status'])?->visualStylesheetPaths())
        ->toBe([
            'presets/constellation/theme.css',
            'presets/constellation/aliases.css',
            'presets/constellation/layout/surfaces.css',
            'presets/constellation/feedback.css',
        ])
        ->and($presets->sourceForSelection('constellation')?->visualStylesheetPaths())
        ->toBe([
            'presets/constellation/theme.css',
            'presets/constellation/aliases.css',
        ])
        ->and($grouped?->visualStylesheetPaths())->toBe(['presets/orbit/all.css'])
        ->and($grouped?->visualCss())->toContain('[data-slot="status"]');
});

it('validates additional properties and aliases across the complete ordered preset base', function (Closure $mutate, string $message) {
    $files = new Filesystem;
    $root = sys_get_temp_dir().'/hotwire-css-preset-tokens-'.uniqid();
    $files->copyDirectory(__DIR__.'/../Fixtures/css/preset-package', $root);
    $mutate($files, $root);

    try {
        expect(fn () => syntheticCssPresetFiles($root)->source('constellation'))
            ->toThrow(PresetSourceException::class, $message);
    } finally {
        $files->deleteDirectory($root);
    }
})->with([
    'missing property' => [
        function (Filesystem $files, string $root): void {
            $path = $root.'/presets/constellation/theme.css';
            $css = preg_replace(
                '/^\h*--fixture-surface-foreground:\h*(?:black|white);\R?/m',
                '',
                $files->get($path),
            );
            $files->put($path, (string) $css);
        },
        'Preset [constellation] property [--fixture-surface-foreground] is not declared by its base sources.',
    ],
    'unregistered foundation alias' => [
        function (Filesystem $files, string $root): void {
            $files->append($root.'/tokens.css', "\n@theme inline { --color-fixture-extra: var(--fixture-background); }\n");
        },
        'Shared foundation @theme inline declares unregistered alias [--color-fixture-extra].',
    ],
    'unregistered property' => [
        function (Filesystem $files, string $root): void {
            $path = $root.'/presets/constellation/aliases.css';
            $files->append($path, "\n:root { --fixture-unregistered: 1rem; }\n");
        },
        'Preset [constellation] base declares unregistered property [--fixture-unregistered].',
    ],
    'missing alias' => [
        function (Filesystem $files, string $root): void {
            $path = $root.'/presets/constellation/aliases.css';
            $css = preg_replace(
                '/^\h*--color-fixture-surface:\h*var\(--fixture-surface\);\R?/m',
                '',
                $files->get($path),
            );
            $files->put($path, (string) $css);
        },
        'Preset [constellation] alias [--color-fixture-surface] is not declared in @theme inline.',
    ],
    'wrong alias target' => [
        function (Filesystem $files, string $root): void {
            $path = $root.'/presets/constellation/aliases.css';
            $files->put($path, str_replace('var(--fixture-surface)', 'var(--fixture-surface-foreground)', $files->get($path)));
        },
        'Preset [constellation] alias [--color-fixture-surface] must reference [--fixture-surface], found [--fixture-surface-foreground].',
    ],
    'unsupported base scope' => [
        function (Filesystem $files, string $root): void {
            $files->append($root.'/presets/constellation/theme.css', "\n[data-slot=fixture] { --fixture-knob: 1; }\n");
        },
        'Preset [constellation] base contains unsupported CSS scope [[data-slot=fixture]].',
    ],
]);

it('extracts foundation declarations outside preset-supported scopes without auditing those scopes', function () {
    $files = new Filesystem;
    $root = sys_get_temp_dir().'/hotwire-css-foundation-tokens-'.uniqid();
    $files->copyDirectory(__DIR__.'/../Fixtures/css/preset-package', $root);
    $tokens = $root.'/tokens.css';
    $css = preg_replace('/^\h*--fixture-background:\h*white;\R?/m', '', $files->get($tokens));
    $files->put($tokens, $css."\n@media (prefers-color-scheme: dark) { :root { --fixture-background: black; } }\n");

    try {
        expect(syntheticCssPresetFiles($root)->source('constellation'))->not->toBeNull();
    } finally {
        $files->deleteDirectory($root);
    }
});

it('reports an unreadable canonical token source as a preset source error', function () {
    $files = new class extends Filesystem
    {
        public function get($path, $lock = false)
        {
            if (str_ends_with($path, '/tokens.css')) {
                throw new FileNotFoundException($path);
            }

            return parent::get($path, $lock);
        }
    };

    expect(fn () => syntheticCssPresetFiles(files: $files)->source('constellation'))
        ->toThrow(PresetSourceException::class, 'Shared foundation token source [tokens.css] cannot be read.');
});

it('reports invalid foundation CSS before comparing registered token names', function (Closure $mutate) {
    $files = new Filesystem;
    $root = sys_get_temp_dir().'/hotwire-css-invalid-foundation-'.uniqid();
    $files->copyDirectory(__DIR__.'/../Fixtures/css/preset-package', $root);
    $tokens = $root.'/tokens.css';
    $files->put($tokens, $mutate($files->get($tokens)));

    try {
        expect(fn () => syntheticCssPresetFiles($root)->source('constellation'))
            ->toThrow(PresetSourceException::class, 'Shared foundation token source [tokens.css] has invalid CSS syntax.');
    } finally {
        $files->deleteDirectory($root);
    }
})->with([
    'unterminated quote' => fn (string $css): string => str_replace(
        '--fixture-background: white;',
        '--fixture-background: "oops;',
        $css,
    ),
    'non-custom declaration in theme' => fn (string $css): string => str_replace(
        '--color-fixture-background: var(--fixture-background);',
        "--color-fixture-background: var(--fixture-background);\n    color: red;",
        $css,
    ),
]);

it('resolves a preset only once when selecting sources', function () {
    $files = new class extends Filesystem
    {
        public int $reads = 0;

        public function get($path, $lock = false)
        {
            $this->reads++;

            return parent::get($path, $lock);
        }
    };
    $presets = syntheticCssPresetFiles(files: $files);
    $presets->source('constellation');
    $fullResolutionReads = $files->reads;
    $files->reads = 0;

    $presets->sourceForSelection('constellation', ['action']);

    expect($files->reads)->toBe($fullResolutionReads);
});

it('diagnoses foundation facade drift in shipped entrypoints', function (Closure $mutate, string $message) {
    $files = new Filesystem;
    $root = sys_get_temp_dir().'/hotwire-css-preset-files-'.uniqid();
    $files->copyDirectory(__DIR__.'/../Fixtures/css/preset-package', $root);
    $entrypoint = $root.'/presets/constellation.css';
    $css = str_replace(["\r\n", "\r"], "\n", $files->get($entrypoint));
    $files->put($entrypoint, $mutate($css));

    try {
        expect(fn () => syntheticCssPresetFiles($root)->source('constellation'))
            ->toThrow(PresetSourceException::class, $message);
    } finally {
        $files->deleteDirectory($root);
    }
})->with([
    'missing' => [
        fn (string $css): string => str_replace('@import "../foundation.css";'."\n", '', $css),
        'must import shared foundation [foundation.css] exactly once',
    ],
    'duplicate' => [
        fn (string $css): string => str_replace(
            '@import "../foundation.css";',
            '@import "../foundation.css";'."\n".'@import "../foundation.css";',
            $css,
        ),
        'imports shared foundation [foundation.css] more than once',
    ],
    'after preset base' => [
        fn (string $css): string => str_replace(
            '@import "../foundation.css";'."\n".'@import "./constellation/theme.css";',
            '@import "./constellation/theme.css";'."\n".'@import "../foundation.css";',
            $css,
        ),
        'must import shared foundations before visual sources',
    ],
    'additional foundation' => [
        fn (string $css): string => str_replace(
            '@import "../foundation.css";',
            '@import "../foundation.css";'."\n".'@import "../foundations/metrics.css";',
            $css,
        ),
        'must import shared foundation [foundation.css] exactly once before preset sources',
    ],
]);

it('diagnoses missing, duplicate, and reordered preset sources', function (Closure $mutate, string $message) {
    $files = new Filesystem;
    $root = sys_get_temp_dir().'/hotwire-css-preset-base-'.uniqid();
    $files->copyDirectory(__DIR__.'/../Fixtures/css/preset-package', $root);
    $entrypoint = $root.'/presets/constellation.css';
    $css = str_replace(["\r\n", "\r"], "\n", $files->get($entrypoint));
    $files->put($entrypoint, $mutate($css));

    try {
        expect(fn () => syntheticCssPresetFiles($root)->source('constellation'))
            ->toThrow(PresetSourceException::class, $message);
    } finally {
        $files->deleteDirectory($root);
    }
})->with([
    'missing' => [
        fn (string $css): string => str_replace('@import "./constellation/theme.css";'."\n", '', $css),
        'does not import declared sources: presets/constellation/theme.css',
    ],
    'duplicate' => [
        fn (string $css): string => str_replace(
            '@import "./constellation/theme.css";',
            '@import "./constellation/theme.css";'."\n".'@import "./constellation/theme.css";',
            $css,
        ),
        'includes visual stylesheet [presets/constellation/theme.css] more than once',
    ],
    'after modules' => [
        fn (string $css): string => str_replace(
            '@import "./constellation/theme.css";'."\n"
                .'@import "./constellation/aliases.css";'."\n"
                .'@import "./constellation/layout/surfaces.css";',
            '@import "./constellation/layout/surfaces.css";'."\n"
                .'@import "./constellation/theme.css";'."\n"
                .'@import "./constellation/aliases.css";',
            $css,
        ),
        'must import preset base before modules in manifest order',
    ],
    'modules outside manifest order' => [
        fn (string $css): string => str_replace(
            '@import "./constellation/layout/surfaces.css";'."\n".'@import "./constellation/feedback.css";',
            '@import "./constellation/feedback.css";'."\n".'@import "./constellation/layout/surfaces.css";',
            $css,
        ),
        'must import module sources in manifest order',
    ],
]);

it('rejects visual declarations in a shipped preset entrypoint', function () {
    $files = new Filesystem;
    $root = sys_get_temp_dir().'/hotwire-css-preset-entrypoint-'.uniqid();
    $files->copyDirectory(__DIR__.'/../Fixtures/css/preset-package', $root);
    $entrypoint = $root.'/presets/constellation.css';
    $files->append($entrypoint, "\n[data-slot=\"entrypoint\"] {}\n");

    try {
        expect(fn () => syntheticCssPresetFiles($root)->source('constellation'))
            ->toThrow(
                PresetSourceException::class,
                'entrypoint imports undeclared sources: presets/constellation.css',
            );
    } finally {
        $files->deleteDirectory($root);
    }
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
        ->and($foundations)->toBe(['foundation.css'])
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
        ->not->toContain('[data-slot="drawer-overlay"]')
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
