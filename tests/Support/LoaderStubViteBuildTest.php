<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\LoaderStub;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

it('keeps eager application and package controllers out of Vite dynamic entries', function () {
    $vite = realpath(__DIR__.'/../../node_modules/.bin/vite');

    if ($vite === false) {
        $this->markTestSkipped('Vite is not installed in the PHP-only test environment.');
    }

    $files = new Filesystem;
    $directory = sys_get_temp_dir().'/hwc-vite-loader-'.uniqid('', true);
    $controllers = $directory.'/resources/js/controllers';
    $vendorControllers = $directory.'/vendor/emaia/laravel-hotwire/resources/js/controllers';
    $files->ensureDirectoryExists($controllers);
    $files->ensureDirectoryExists($directory.'/resources/js/libs');
    $files->ensureDirectoryExists($vendorControllers);
    $files->ensureDirectoryExists($directory.'/node_modules/@emaia/stimulus-lazy-loader');

    $files->put($controllers.'/lazy_controller.js', 'export default class Lazy {}');
    $files->put($controllers.'/eager_controller.js', 'export default class Eager {}');
    $files->put($vendorControllers.'/package_lazy_controller.js', 'export default class PackageLazy {}');
    $files->put($vendorControllers.'/package_eager_controller.js', 'export default class PackageEager {}');
    $files->put($directory.'/resources/js/libs/stimulus.js', 'export const Stimulus = { register() {} };');
    $files->put(
        $directory.'/node_modules/@emaia/stimulus-lazy-loader/package.json',
        json_encode(['type' => 'module', 'exports' => './index.js'], JSON_THROW_ON_ERROR),
    );
    $files->put(
        $directory.'/node_modules/@emaia/stimulus-lazy-loader/index.js',
        'export function registerControllers() {}',
    );

    $registry = HotwireRegistry::fromCatalog([
        'components' => [],
        'controllers' => [
            'package-eager' => [
                'source' => 'resources/js/controllers/package_eager_controller.js',
                'docs' => 'unused',
                'category' => 'utility',
            ],
            'package-lazy' => [
                'source' => 'resources/js/controllers/package_lazy_controller.js',
                'docs' => 'unused',
                'category' => 'utility',
            ],
        ],
    ], $directory.'/vendor/emaia/laravel-hotwire');
    $files->put(
        $controllers.'/index.js',
        LoaderStub::generate(
            $registry,
            eagerControllers: ['eager', 'package-eager'],
            appControllersPath: $controllers,
        ),
    );
    $files->put($directory.'/resources/js/app.js', 'import "./controllers/index.js";');
    $files->put($directory.'/package.json', json_encode(['type' => 'module'], JSON_THROW_ON_ERROR));
    $files->put($directory.'/vite.config.js', <<<'JS'
        export default {
            build: {
                manifest: true,
                rollupOptions: { input: "resources/js/app.js" },
            },
        };
        JS);

    $process = new Process([
        $vite,
        'build',
        '--config',
        $directory.'/vite.config.js',
    ], $directory, ['NODE_PATH' => realpath(__DIR__.'/../../node_modules')]);
    $process->setTimeout(60);

    try {
        $process->mustRun();
        $manifest = json_decode(
            $files->get($directory.'/dist/.vite/manifest.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $dynamicSources = array_values(array_filter(array_map(
            fn (array $entry): ?string => ($entry['isDynamicEntry'] ?? false) ? ($entry['src'] ?? null) : null,
            $manifest,
        )));

        expect($process->getErrorOutput())
            ->not->toContain('INEFFECTIVE_DYNAMIC_IMPORT')
            ->and($dynamicSources)
            ->toContain('resources/js/controllers/lazy_controller.js')
            ->toContain('vendor/emaia/laravel-hotwire/resources/js/controllers/package_lazy_controller.js')
            ->not->toContain('resources/js/controllers/eager_controller.js')
            ->not->toContain('vendor/emaia/laravel-hotwire/resources/js/controllers/package_eager_controller.js');
    } finally {
        $files->deleteDirectory($directory);
    }
});
