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

it('runs CSS contracts for every PHP source that defines preset scaffolds', function (string $path) {
    $workflow = File::get(__DIR__.'/../../.github/workflows/run-js-tests.yml');

    expect(substr_count($workflow, "- '{$path}'"))->toBe(2);
})->with([
    'component families' => 'src/Components/**',
    'registry contract' => 'src/Registry/**',
    'CSS support' => 'src/Support/Css*.php',
    'preset support' => 'src/Support/Preset*.php',
]);
