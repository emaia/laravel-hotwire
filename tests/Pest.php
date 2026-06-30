<?php

use Emaia\LaravelHotwire\Support\PackageInstaller;
use Emaia\LaravelHotwire\Tests\Support\FakePackageInstaller;
use Emaia\LaravelHotwire\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * Bind a FakePackageInstaller into the container so command tests can assert on
 * the manager invocation without spawning the real bun/pnpm/yarn/npm binary
 * (which CI doesn't have installed).
 */
function fakePackageInstaller(string $manager = 'bun', int $exitCode = 0): FakePackageInstaller
{
    $fake = new FakePackageInstaller($manager, $exitCode);
    app()->instance(PackageInstaller::class, $fake);

    return $fake;
}
