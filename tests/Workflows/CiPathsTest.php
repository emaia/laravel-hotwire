<?php

use Illuminate\Support\Facades\File;

it('runs PHP contract tests for documentation-only changes', function () {
    $workflow = File::get(__DIR__.'/../../.github/workflows/run-tests.yml');

    expect(substr_count($workflow, "- 'docs/**'"))->toBe(2);
});

it('runs browser contracts for package component template changes', function () {
    $workflow = File::get(__DIR__.'/../../.github/workflows/run-js-tests.yml');

    expect(substr_count($workflow, "- 'resources/views/component-views/**'"))->toBe(2);
});

it('provides the PHP manifest bridge to browser contrast tests', function () {
    $workflow = File::get(__DIR__.'/../../.github/workflows/run-js-tests.yml');
    $browser = explode('  browser:', $workflow, 2)[1] ?? '';

    expect(substr_count($workflow, "- 'scripts/preset_contrast_manifest.php'"))->toBe(2)
        ->and($browser)->toContain('shivammathur/setup-php@v2')
        ->toContain('composer install --prefer-dist --no-interaction');
});

it('runs CSS contracts for every PHP source that defines preset scaffolds', function (string $path) {
    $workflow = File::get(__DIR__.'/../../.github/workflows/run-js-tests.yml');

    expect(substr_count($workflow, "- '{$path}'"))->toBe(2);
})->with([
    'component families' => 'src/Components/**',
    'registry contract' => 'src/Registry/**',
    'CSS support' => 'src/Support/Css*.php',
    'preset support' => 'src/Support/Preset*.php',
    'foundation facade' => 'src/Support/FoundationFacade.php',
    'Composer manifest' => 'composer.json',
    'Composer lock' => 'composer.lock',
]);
