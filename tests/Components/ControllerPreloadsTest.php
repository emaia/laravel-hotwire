<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Emaia\LaravelHotwire\Support\ViteManifestCache;
use Illuminate\Container\Container;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // The diagnostic comment is opt-in behaviour gated on app.debug, and two tests below
    // turn it on deliberately. Pin the off state here so the tests that assert silence do
    // not inherit it from the ambient environment: Testbench boots from its own skeleton,
    // whose .env carries APP_DEBUG=true when one exists on the machine.
    config()->set('app.debug', false);

    $this->appBase = isolateAppPaths();
    File::ensureDirectoryExists(public_path('build'));
    File::put(base_path('composer.json'), json_encode([
        'name' => 'test/app',
        'require' => ['laravel/framework' => '^12.0'],
    ], JSON_THROW_ON_ERROR));
});

afterEach(function () {
    app(Vite::class)->flush();
    app(ViteManifestCache::class)->flush();
    releaseIsolatedAppPaths($this->appBase);
});

it('renders configured controller modulepreloads with recursive imports', function () {
    config()->set('hotwire.controllers.preload', ['reveal']);
    File::put(public_path('build/manifest.json'), json_encode([
        'resources/js/controllers/reveal_controller.js' => [
            'file' => 'assets/reveal-123.js',
            'imports' => ['_stimulus.js'],
            'integrity' => 'sha384-reveal',
        ],
        '_stimulus.js' => [
            'file' => 'assets/stimulus-123.js',
            'integrity' => 'sha384-stimulus',
        ],
    ], JSON_THROW_ON_ERROR));
    app(Vite::class)->useCspNonce('test-nonce');

    $html = (string) $this->blade('<x-hw::controller-preloads />');

    expect($html)
        ->toContain('rel="modulepreload"')
        ->toContain('href="http://localhost/build/assets/reveal-123.js"')
        ->toContain('href="http://localhost/build/assets/stimulus-123.js"')
        ->toContain('integrity="sha384-reveal"')
        ->toContain('nonce="test-nonce"');
});

it('accepts an explicit page-level controller selection', function () {
    config()->set('hotwire.controllers.preload', ['ignored']);
    File::put(public_path('build/manifest.json'), json_encode([
        'resources/js/controllers/search_controller.ts' => [
            'file' => 'assets/search.js',
        ],
    ], JSON_THROW_ON_ERROR));

    $html = (string) $this->blade('<x-hw::controller-preloads controllers="search" />');

    expect($html)
        ->toContain('assets/search.js')
        ->not->toContain('ignored');
});

it('normalizes an explicit array controller selection', function () {
    File::put(public_path('build/manifest.json'), json_encode([
        'resources/js/controllers/search_controller.ts' => [
            'file' => 'assets/search.js',
        ],
    ], JSON_THROW_ON_ERROR));

    $html = (string) $this->blade(
        '<x-hw::controller-preloads :controllers="[\' search \']" />',
    );

    expect($html)->toContain('assets/search.js');
});

it('logs and renders nothing when a controller is absent from the manifest', function () {
    Log::spy();
    File::put(public_path('build/manifest.json'), json_encode([], JSON_THROW_ON_ERROR));

    $html = (string) $this->blade('<x-hw::controller-preloads controllers="missing" />');

    expect(trim($html))->toBe('');
    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message): bool => str_contains($message, 'Unable to locate Stimulus controller [missing]'));
});

it('renders a diagnostic comment for missing controllers when debug is enabled', function () {
    Log::spy();
    config()->set('app.debug', true);
    File::put(public_path('build/manifest.json'), json_encode([], JSON_THROW_ON_ERROR));

    $html = (string) $this->blade('<x-hw::controller-preloads controllers="missing" />');

    expect($html)
        ->toContain('Laravel Hotwire controller preload warning')
        ->toContain('Unable to locate Stimulus controller [missing]')
        ->not->toContain('modulepreload');
    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message): bool => str_contains($message, 'Unable to locate Stimulus controller [missing]'));
});

it('skips a missing controller without dropping valid preloads', function () {
    Log::spy();
    File::put(public_path('build/manifest.json'), json_encode([
        'resources/js/controllers/modal_controller.js' => [
            'file' => 'assets/modal.js',
            'imports' => ['_stimulus.js'],
        ],
        'resources/js/controllers/rich_text_controller.js' => [
            'file' => 'assets/rich-text.js',
            'imports' => ['_stimulus.js'],
        ],
        '_stimulus.js' => [
            'file' => 'assets/stimulus.js',
        ],
    ], JSON_THROW_ON_ERROR));

    $html = (string) $this->blade(
        '<x-hw::controller-preloads :controllers="[\'modal\', \'missing\', \'rich-text\']" />',
    );

    expect($html)
        ->toContain('assets/modal.js')
        ->toContain('assets/rich-text.js')
        ->and(substr_count($html, 'assets/stimulus.js'))->toBe(1);
    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message): bool => str_contains($message, 'Unable to locate Stimulus controller [missing]'));
});

it('keeps valid preloads with diagnostic comments when debug is enabled', function () {
    Log::spy();
    config()->set('app.debug', true);
    File::put(public_path('build/manifest.json'), json_encode([
        'resources/js/controllers/modal_controller.js' => [
            'file' => 'assets/modal.js',
        ],
    ], JSON_THROW_ON_ERROR));

    $html = (string) $this->blade(
        '<x-hw::controller-preloads :controllers="[\'modal\', \'missing\']" />',
    );

    expect($html)
        ->toContain('assets/modal.js')
        ->toContain('Laravel Hotwire controller preload warning')
        ->toContain('Unable to locate Stimulus controller [missing]');
});

it('logs and renders nothing when the Vite manifest is unavailable', function () {
    Log::spy();
    File::delete(public_path('build/manifest.json'));

    $html = (string) $this->blade('<x-hw::controller-preloads controllers="search" />');

    expect(trim($html))->toBe('');
    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message): bool => str_contains($message, 'Vite manifest not found'));
});

it('uses Vite preload attribute callbacks and avoids duplicate tags', function () {
    File::put(public_path('build/manifest.json'), json_encode([
        'resources/js/controllers/search_controller.js' => [
            'file' => 'assets/search.js',
        ],
    ], JSON_THROW_ON_ERROR));
    app(Vite::class)->usePreloadTagAttributes(['data-test' => 'controller-preload']);

    $html = (string) $this->blade(
        '<x-hw::controller-preloads controllers="search" /><x-hw::controller-preloads controllers="search" />',
    );

    expect(substr_count($html, 'href="http://localhost/build/assets/search.js"'))->toBe(1)
        ->and($html)->toContain('data-test="controller-preload"');
});

it('does not duplicate an asset already preloaded by the Vite entrypoint', function () {
    File::put(public_path('build/manifest.json'), json_encode([
        'resources/js/app.js' => [
            'file' => 'assets/app.js',
            'src' => 'resources/js/app.js',
            'isEntry' => true,
            'imports' => ['resources/js/controllers/search_controller.js'],
        ],
        'resources/js/controllers/search_controller.js' => [
            'file' => 'assets/search.js',
            'src' => 'resources/js/controllers/search_controller.js',
        ],
    ], JSON_THROW_ON_ERROR));

    $html = app(Vite::class)(['resources/js/app.js']);
    $html .= (string) $this->blade('<x-hw::controller-preloads controllers="search" />');

    expect(substr_count($html, 'href="http://localhost/build/assets/search.js"'))->toBe(1);
});

it('does not emit Vite entrypoint preloads before the entrypoint is rendered', function () {
    File::put(public_path('build/manifest.json'), json_encode([
        'resources/js/app.js' => [
            'file' => 'assets/app.js',
            'src' => 'resources/js/app.js',
            'isEntry' => true,
            'css' => ['assets/app.css'],
        ],
        'resources/css/app.css' => [
            'file' => 'assets/app.css',
            'src' => 'resources/css/app.css',
            'isEntry' => true,
        ],
        'resources/js/controllers/search_controller.js' => [
            'file' => 'assets/search.js',
            'src' => 'resources/js/controllers/search_controller.js',
            'imports' => ['resources/js/app.js'],
        ],
    ], JSON_THROW_ON_ERROR));

    $html = (string) $this->blade('<x-hw::controller-preloads controllers="search" />');

    expect($html)
        ->toContain('href="http://localhost/build/assets/search.js"')
        ->not->toContain('href="http://localhost/build/assets/app.js"')
        ->not->toContain('href="http://localhost/build/assets/app.css"');
});

it('uses custom Vite build directories and manifest filenames', function () {
    File::ensureDirectoryExists(public_path('assets'));
    File::put(public_path('assets/custom.json'), json_encode([
        'resources/js/controllers/search_controller.js' => [
            'file' => 'chunks/search.js',
        ],
    ], JSON_THROW_ON_ERROR));
    app(Vite::class)
        ->useBuildDirectory('assets')
        ->useManifestFilename('custom.json');

    $html = (string) $this->blade('<x-hw::controller-preloads controllers="search" />');

    expect($html)->toContain('href="http://localhost/assets/chunks/search.js"');
});

it('supports a build directory passed for an invocation-specific Vite build', function () {
    File::ensureDirectoryExists(public_path('custom-build'));
    File::put(public_path('custom-build/manifest.json'), json_encode([
        'resources/js/controllers/search_controller.js' => [
            'file' => 'chunks/search.js',
        ],
    ], JSON_THROW_ON_ERROR));

    $html = (string) $this->blade(
        '<x-hw::controller-preloads controllers="search" build-directory="custom-build" />',
    );

    expect($html)->toContain('href="http://localhost/custom-build/chunks/search.js"');
});

it('preloads CSS imported by controller chunks', function () {
    File::put(public_path('build/manifest.json'), json_encode([
        'resources/js/controllers/map_controller.js' => [
            'file' => 'assets/map.js',
            'css' => ['assets/map.css'],
        ],
        'resources/css/map.css' => [
            'file' => 'assets/map.css',
            'src' => 'resources/css/map.css',
            'integrity' => 'sha384-map-css',
        ],
    ], JSON_THROW_ON_ERROR));

    $html = (string) $this->blade('<x-hw::controller-preloads controllers="map" />');

    expect($html)
        ->toContain('rel="preload"')
        ->toContain('as="style"')
        ->toContain('href="http://localhost/build/assets/map.css"')
        ->toContain('integrity="sha384-map-css"');
});

it('does not reuse a parent JavaScript integrity hash for attached CSS', function () {
    File::put(public_path('build/manifest.json'), json_encode([
        'resources/js/controllers/map_controller.js' => [
            'file' => 'assets/map.js',
            'css' => ['assets/map.css'],
            'hash' => 'sha384-map-js',
        ],
    ], JSON_THROW_ON_ERROR));
    app(Vite::class)->useIntegrityKey('hash');

    $html = (string) $this->blade('<x-hw::controller-preloads controllers="map" />');

    expect($html)
        ->toContain('href="http://localhost/build/assets/map.css"')
        ->and(substr_count($html, 'integrity="sha384-map-js"'))->toBe(1);
});

it('renders nothing while Vite is running hot', function () {
    File::put(public_path('hot'), 'http://localhost:5173');

    expect(trim((string) $this->blade('<x-hw::controller-preloads controllers="missing" />')))->toBe('')
        ->and(app()->resolved(ViteManifestCache::class))->toBeFalse();
});

it('renders nothing when no controllers are selected', function () {
    config()->set('hotwire.controllers.preload', []);

    expect(trim((string) $this->blade('<x-hw::controller-preloads />')))->toBe('');
});

it('does not preload controllers already configured as eager', function () {
    config()->set('hotwire.controllers.preload', ['reveal']);
    config()->set('hotwire.controllers.eager', ['reveal']);

    expect(trim((string) $this->blade('<x-hw::controller-preloads />')))->toBe('');
});

it('normalizes eager identifiers before filtering explicit preloads', function () {
    config()->set('hotwire.controllers.preload', []);
    config()->set('hotwire.controllers.eager', [' modal ']);

    expect(trim((string) $this->blade('<x-hw::controller-preloads controllers="modal" />')))->toBe('');
});

it('flushes Vite state at termination only after controller preloads are used', function () {
    $vite = new class extends Vite
    {
        public int $flushes = 0;

        public function flush(): void
        {
            $this->flushes++;
            parent::flush();
        }
    };
    app()->instance(Vite::class, $vite);

    app()->terminate();

    expect($vite->flushes)->toBe(0);

    File::put(public_path('build/manifest.json'), json_encode([
        'resources/js/controllers/search_controller.js' => [
            'file' => 'assets/search.js',
        ],
    ], JSON_THROW_ON_ERROR));
    $vite->controllerPreloads(['search']);
    app()->terminate();

    expect($vite->flushes)->toBe(1);
});

it('flushes Vite state using the active request sandbox container', function () {
    $worker = app();
    $sandbox = clone $worker;
    $vite = new class extends Vite
    {
        public int $flushes = 0;

        public function flush(): void
        {
            $this->flushes++;
            parent::flush();
        }
    };
    File::put(public_path('build/manifest.json'), json_encode([
        'resources/js/controllers/search_controller.js' => [
            'file' => 'assets/search.js',
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        Container::setInstance($sandbox);
        $vite->controllerPreloads(['search']);
        $worker->terminate();

        expect($vite->flushes)->toBe(1);
    } finally {
        Container::setInstance($worker);
    }
});

it('keeps preload cleanup compatible with Vite versions before flush was added', function () {
    $vite = new class
    {
        protected array $preloadedAssets = ['asset.js'];

        public function preloadedAssets(): array
        {
            return $this->preloadedAssets;
        }
    };
    $flush = new ReflectionMethod(LaravelHotwireServiceProvider::class, 'flushViteState');
    $flush->invoke(null, $vite);

    expect($vite->preloadedAssets())->toBe([]);
});
