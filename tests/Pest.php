<?php

use Emaia\LaravelHotwire\Support\PackageInstaller;
use Emaia\LaravelHotwire\Tests\Support\FakePackageInstaller;
use Emaia\LaravelHotwire\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ViewErrorBag;

uses(TestCase::class)->in(__DIR__);

/**
 * Bind a FakePackageInstaller into the container so command tests can assert on
 * the manager invocation without spawning the real bun/pnpm/yarn/npm binary
 * (which CI doesn't have installed).
 */
function fakePackageInstaller(?string $manager = 'bun', int $exitCode = 0): FakePackageInstaller
{
    $fake = new FakePackageInstaller($manager, $exitCode);
    app()->instance(PackageInstaller::class, $fake);

    return $fake;
}

/**
 * Point Laravel's basePath at a fresh, per-test temp directory, so command tests
 * (which write to `resource_path()`/`base_path()`) don't stomp on the shared
 * Testbench app fixture. Required for `--parallel`, where every worker would
 * otherwise contend for the same paths under vendor/orchestra/testbench-core/laravel.
 *
 * Called from `beforeEach` in each command-suite file; the matching `afterEach`
 * deletes the temp dir. Returns the isolated base path so tests can capture it.
 */
function isolateAppPaths(): string
{
    $token = getenv('TEST_TOKEN') ?: 'seq';
    $base = sys_get_temp_dir().'/hwc-test-'.$token.'-'.uniqid('', true);

    File::ensureDirectoryExists($base.'/resources/views');

    app()->setBasePath($base);

    // The default PackageInstaller shells out to the real bun/pnpm/yarn/npm
    // binary — expensive under isolation (no cached node_modules) and non-deterministic
    // on CI. Bind a fake that keeps the real lock-file detect()
    // but stubs install(); tests that want to force a manager re-bind with
    // `fakePackageInstaller('bun'|'pnpm'|…)`.
    fakePackageInstaller(manager: null);

    return $base;
}

/**
 * Tear down an isolated app-path base created by `isolateAppPaths()`.
 */
function releaseIsolatedAppPaths(?string $base): void
{
    if ($base !== null && is_dir($base)) {
        File::deleteDirectory($base);
    }
}

function dom(string $html): DOMDocument
{
    $dom = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    return $dom;
}

function componentRequiresRenderProps(string $key, ?ReflectionMethod $constructor): bool
{
    return in_array($key, ['chart', 'file-upload', 'frame-or-page', 'frame-or-page.frame', 'frame-or-page.page', 'map'], true)
        || ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0);
}

function renderAxisComponent(string $key): string
{
    view()->share('errors', new ViewErrorBag);

    $markup = match ($key) {
        'drawer' => '<x-hw::drawer aria-label="Example"><x-hw::drawer.content /></x-hw::drawer>',
        'hover-card.trigger' => '<x-hw::hover-card><x-hw::hover-card.trigger /></x-hw::hover-card>',
        'modal' => '<x-hw::modal aria-label="Example"><x-hw::modal.content /></x-hw::modal>',
        'sheet' => '<x-hw::sheet aria-label="Example"><x-hw::sheet.content /></x-hw::sheet>',
        'toggle-group' => '<x-hw::toggle-group><x-hw::toggle-group.item value="one">One</x-hw::toggle-group.item></x-hw::toggle-group>',
        default => "<x-hw::{$key} />",
    };

    return Blade::render($markup);
}

/** @return string[] */
function renderedAxisSlots(string $html, string $axis, string $value): array
{
    $nodes = (new DOMXPath(dom($html)))->query("//*[@data-slot and @data-{$axis}]");
    $slots = [];

    foreach ($nodes ?: [] as $node) {
        if ($node instanceof DOMElement && $node->getAttribute("data-{$axis}") === $value) {
            $slots[] = $node->getAttribute('data-slot');
        }
    }

    return array_values(array_unique($slots));
}

/** @return string[] */
function renderedSlots(string $html): array
{
    $nodes = (new DOMXPath(dom($html)))->query('//*[@data-slot]');
    $slots = [];

    foreach ($nodes ?: [] as $node) {
        if ($node instanceof DOMElement) {
            $slots[] = $node->getAttribute('data-slot');
        }
    }

    return array_values(array_unique($slots));
}
