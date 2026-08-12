<?php

use Emaia\LaravelHotwire\Support\ViteControllerAsset;
use Emaia\LaravelHotwire\Support\ViteControllerAssetResolver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Vite;

function controllerAssetResolver(array|string $manifest): ViteControllerAssetResolver
{
    return new ViteControllerAssetResolver(
        $manifest,
        fn (string $manifestKey, string $file): string => 'https://assets.example.test/build/'.$file,
    );
}

it('resolves local and package controllers by source path with local controllers taking precedence', function () {
    $manifest = [
        'local-admin-entry' => [
            'file' => 'assets/local-admin.js',
            'src' => 'resources/js/controllers/turbo/admin_panel_controller.ts',
            'integrity' => 'sha384-local',
            'name' => 'not-the-controller-identifier',
        ],
        '../../../vendor/emaia/laravel-hotwire/resources/js/controllers/turbo/admin_panel_controller.js' => [
            'file' => 'assets/package-admin.js',
            'src' => '../../../vendor/emaia/laravel-hotwire/resources/js/controllers/turbo/admin_panel_controller.js',
        ],
        'vendor/emaia/laravel-hotwire/resources/js/controllers/copy_to_clipboard_controller.js' => [
            'file' => 'assets/copy.js',
        ],
        '../laravel-hotwire/resources/js/controllers/dev/log_controller.js' => [
            'file' => 'assets/dev-log.js',
            'src' => '../laravel-hotwire/resources/js/controllers/dev/log_controller.js',
        ],
    ];

    expect(controllerAssetResolver($manifest)->resolve([
        'turbo--admin-panel',
        'copy-to-clipboard',
        'dev--log',
    ]))->toEqual([
        new ViteControllerAsset(
            manifestKey: 'local-admin-entry',
            source: 'resources/js/controllers/turbo/admin_panel_controller.ts',
            file: 'assets/local-admin.js',
            url: 'https://assets.example.test/build/assets/local-admin.js',
            integrity: 'sha384-local',
        ),
        new ViteControllerAsset(
            manifestKey: 'vendor/emaia/laravel-hotwire/resources/js/controllers/copy_to_clipboard_controller.js',
            source: 'vendor/emaia/laravel-hotwire/resources/js/controllers/copy_to_clipboard_controller.js',
            file: 'assets/copy.js',
            url: 'https://assets.example.test/build/assets/copy.js',
        ),
        new ViteControllerAsset(
            manifestKey: '../laravel-hotwire/resources/js/controllers/dev/log_controller.js',
            source: '../laravel-hotwire/resources/js/controllers/dev/log_controller.js',
            file: 'assets/dev-log.js',
            url: 'https://assets.example.test/build/assets/dev-log.js',
        ),
    ]);
});

it('matches lazy-loader v2 path prefixes for nested controllers directories', function () {
    $manifest = [
        'resources/js/controllers/admin/controllers/report_controller.js' => [
            'file' => 'assets/report.js',
        ],
    ];

    expect(controllerAssetResolver($manifest)->resolve(['report']))->toHaveCount(1);
});

it('removes only the first reserved controller path prefix in manifests', function () {
    $manifest = [
        'resources/js/controllers/controllers/admin/controllers/report_controller.js' => [
            'file' => 'assets/report.js',
        ],
    ];

    expect(controllerAssetResolver($manifest)->resolve(['admin--controllers--report']))->toHaveCount(1);
});

it('returns controller chunks and recursive static imports once without following dynamic imports', function () {
    $manifest = [
        'resources/js/controllers/alpha_controller.js' => [
            'file' => 'assets/alpha.js',
            'imports' => ['assets/shared-a.js'],
            'dynamicImports' => ['missing-dynamic.js'],
        ],
        'assets/shared-a.js' => [
            'file' => 'assets/shared-a.js',
            'imports' => ['assets/shared-b.js'],
            'integrity' => 'sha384-shared-a',
        ],
        'assets/shared-b.js' => [
            'file' => 'assets/shared-b.js',
            'imports' => ['assets/shared-a.js'],
        ],
        'resources/js/controllers/beta_controller.ts' => [
            'file' => 'assets/beta.js',
            'imports' => ['assets/shared-b.js'],
        ],
    ];

    $assets = controllerAssetResolver($manifest)->resolve(['alpha', 'beta', 'alpha']);

    expect(array_map(fn (ViteControllerAsset $asset): string => $asset->manifestKey, $assets))->toBe([
        'resources/js/controllers/alpha_controller.js',
        'assets/shared-a.js',
        'assets/shared-b.js',
        'resources/js/controllers/beta_controller.ts',
    ])->and($assets[1]->integrity)->toBe('sha384-shared-a');
});

it('loads a manifest from a JSON file', function () {
    $directory = sys_get_temp_dir().'/hotwire-vite-manifest-'.uniqid('', true);
    mkdir($directory);
    $path = $directory.'/manifest.json';
    file_put_contents($path, json_encode([
        'resources/js/controllers/search_controller.js' => [
            'file' => 'assets/search.js',
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        expect(controllerAssetResolver($path)->resolve(['search']))->toHaveCount(1);
    } finally {
        (new Filesystem)->deleteDirectory($directory);
    }
});

it('uses the public Laravel Vite asset API for configured build directories and asset URLs', function () {
    $basePath = isolateAppPaths();
    $manifest = [
        'resources/js/controllers/search_controller.js' => [
            'file' => 'assets/search-123.js',
            'css' => ['assets/search-123.css'],
        ],
    ];
    $files = new Filesystem;
    $files->ensureDirectoryExists($basePath.'/public/custom-build');
    $files->put($basePath.'/public/custom-build/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
    $vite = (new Vite)->createAssetPathsUsing(
        fn (string $path): string => 'https://cdn.example.test/'.$path,
    );

    try {
        $assets = ViteControllerAssetResolver::usingVite(
            $manifest,
            $vite,
            buildDirectory: 'custom-build',
        )->resolve(['search']);

        expect($assets[0]->url)->toBe('https://cdn.example.test/custom-build/assets/search-123.js')
            ->and($assets[1]->url)->toBe('https://cdn.example.test/custom-build/assets/search-123.css');
    } finally {
        releaseIsolatedAppPaths($basePath);
    }
});

it('errors when a requested controller is unknown', function () {
    expect(fn () => controllerAssetResolver([])->resolve(['missing']))
        ->toThrow(RuntimeException::class, 'Unable to locate Stimulus controller [missing] in the Vite manifest.');
});

it('errors when the winning controller source has ambiguous candidates', function () {
    $manifest = [
        'resources/js/controllers/editor_controller.js' => ['file' => 'assets/editor-js.js'],
        'resources/js/controllers/editor_controller.ts' => ['file' => 'assets/editor-ts.js'],
        'vendor/emaia/laravel-hotwire/resources/js/controllers/editor_controller.js' => [
            'file' => 'assets/package-editor.js',
        ],
    ];

    expect(fn () => controllerAssetResolver($manifest)->resolve(['editor']))
        ->toThrow(RuntimeException::class, 'Ambiguous local Stimulus controller [editor] in the Vite manifest');
});

it('errors when package controller candidates are ambiguous', function () {
    $manifest = [
        'vendor/emaia/laravel-hotwire/resources/js/controllers/editor_controller.js' => [
            'file' => 'assets/editor-one.js',
        ],
        '../laravel-hotwire/resources/js/controllers/editor_controller.ts' => [
            'file' => 'assets/editor-two.js',
        ],
    ];

    expect(fn () => controllerAssetResolver($manifest)->resolve(['editor']))
        ->toThrow(RuntimeException::class, 'Ambiguous package Stimulus controller [editor] in the Vite manifest');
});

it('errors when a static import is missing from the manifest', function () {
    $manifest = [
        'resources/js/controllers/editor_controller.js' => [
            'file' => 'assets/editor.js',
            'imports' => ['assets/missing.js'],
        ],
    ];

    expect(fn () => controllerAssetResolver($manifest)->resolve(['editor']))
        ->toThrow(RuntimeException::class, 'Vite manifest import [assets/missing.js] referenced by [resources/js/controllers/editor_controller.js] is missing.');
});

it('errors when static imports are malformed', function () {
    $manifest = [
        'resources/js/controllers/editor_controller.js' => [
            'file' => 'assets/editor.js',
            'imports' => 'assets/shared.js',
        ],
    ];

    expect(fn () => controllerAssetResolver($manifest)->resolve(['editor']))
        ->toThrow(RuntimeException::class, 'Vite manifest entry [resources/js/controllers/editor_controller.js] has malformed imports.');
});

it('errors when an imported chunk is malformed', function () {
    $manifest = [
        'resources/js/controllers/editor_controller.js' => [
            'file' => 'assets/editor.js',
            'imports' => ['assets/shared.js'],
        ],
        'assets/shared.js' => ['name' => 'shared'],
    ];

    expect(fn () => controllerAssetResolver($manifest)->resolve(['editor']))
        ->toThrow(RuntimeException::class, 'Vite manifest entry [assets/shared.js] has no valid file.');
});

it('errors when a manifest path is missing or contains invalid JSON', function () {
    $directory = sys_get_temp_dir().'/hotwire-vite-manifest-'.uniqid('', true);
    mkdir($directory);
    $invalidPath = $directory.'/manifest.json';
    file_put_contents($invalidPath, '{');

    try {
        expect(fn () => controllerAssetResolver($directory.'/missing.json'))
            ->toThrow(RuntimeException::class, 'Vite manifest not found at')
            ->and(fn () => controllerAssetResolver($invalidPath))
            ->toThrow(RuntimeException::class, 'Unable to decode Vite manifest at');
    } finally {
        (new Filesystem)->deleteDirectory($directory);
    }
});
