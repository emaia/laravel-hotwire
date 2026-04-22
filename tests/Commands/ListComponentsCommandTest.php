<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->targetDir = resource_path('js/controllers');
    File::deleteDirectory($this->targetDir);
});

afterEach(function () {
    File::deleteDirectory($this->targetDir);
});

it('runs successfully', function () {
    $this->artisan('hotwire:components')->assertSuccessful();
});

it('lists all registered components', function () {
    Artisan::call('hotwire:components');
    $output = Artisan::output();

    expect($output)->toContain('Dialog')
        ->and($output)->toContain('Confirm Dialog')
        ->and($output)->toContain('Flash Container')
        ->and($output)->toContain('Flash Message')
        ->and($output)->toContain('Loader');
});

it('shows blade tags with current prefix', function () {
    $this->artisan('hotwire:components')
        ->expectsOutputToContain('<x-hwc::dialog>')
        ->expectsOutputToContain('<x-hwc::confirm-dialog>')
        ->expectsOutputToContain('<x-hwc::flash-message>')
        ->expectsOutputToContain('<x-hwc::loader>')
        ->assertSuccessful();
});

it('shows blade tags respecting custom prefix', function () {
    config()->set('hotwire.prefix', 'h');

    $this->artisan('hotwire:components')
        ->expectsOutputToContain('<x-h::dialog>')
        ->assertSuccessful();
});

it('shows stimulus controller identifiers', function () {
    $this->artisan('hotwire:components')
        ->expectsOutputToContain('confirm-dialog')
        ->expectsOutputToContain('toaster') // before toast: toaster ⊃ toast in Mockery matching
        ->expectsOutputToContain('toast')
        ->expectsOutputToContain('timeago')
        ->assertSuccessful();
});

it('shows dash for component with no controller dependency', function () {
    $this->artisan('hotwire:components')
        ->expectsOutputToContain('—')
        ->assertSuccessful();
});

it('shows not published when controller file is absent', function () {
    $this->artisan('hotwire:components')
        ->expectsOutputToContain('not published')
        ->assertSuccessful();
});

it('shows up to date when installed controller matches package version', function () {
    $source = realpath(__DIR__.'/../../resources/js/controllers/dialog_controller.js');
    $target = $this->targetDir.'/dialog_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::copy($source, $target);

    $this->artisan('hotwire:components')
        ->expectsOutputToContain('up to date')
        ->assertSuccessful();
});

it('shows outdated when installed controller differs from package version', function () {
    $target = $this->targetDir.'/dialog_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, '// modified');

    $this->artisan('hotwire:components')
        ->expectsOutputToContain('outdated')
        ->assertSuccessful();
});

it('lists the flash container and flash message controllers', function () {
    $this->artisan('hotwire:components')
        ->expectsOutputToContain('toaster') // before toast: toaster ⊃ toast in Mockery matching
        ->expectsOutputToContain('toast')
        ->assertSuccessful();
});

it('shows component name only on first row for each component', function () {
    // Artisan::call() captures output in Artisan::output(); $this->artisan() uses its own buffer
    Artisan::call('hotwire:components');
    $text = Artisan::output();
    $occurrences = substr_count($text, 'Flash Message');
    expect($occurrences)->toBe(1);
});
