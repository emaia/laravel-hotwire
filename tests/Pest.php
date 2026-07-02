<?php

use Emaia\LaravelHotwire\Support\PackageInstaller;
use Emaia\LaravelHotwire\Tests\Support\FakePackageInstaller;
use Emaia\LaravelHotwire\Tests\TestCase;
use Illuminate\Support\Facades\File;

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
