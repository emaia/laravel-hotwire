<?php

use Emaia\LaravelHotwire\Support\LoaderSync;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->stubPath = realpath(__DIR__.'/../../stubs/resources/js/controllers/index.js');
    $this->targetDir = resource_path('js/controllers');
    $this->targetPath = $this->targetDir.'/index.js';

    File::deleteDirectory(resource_path('js'));
});

afterEach(function () {
    File::deleteDirectory(resource_path('js'));
});

// --- SyncLoader command ---

it('writes the index when it does not exist', function () {
    $this->artisan('hotwire:sync-loader')
        ->assertSuccessful()
        ->expectsOutput('Loader index synced.');

    expect(File::exists($this->targetPath))->toBeTrue();

    $content = File::get($this->targetPath);
    expect($content)->toContain('@hotwire-loader v');
    expect($content)->toContain('registerControllers(Stimulus, packageControllers);');
    expect($content)->toContain('registerControllers(Stimulus, userControllers);');
});

it('includes exclusion patterns for com-dep controllers', function () {
    $this->artisan('hotwire:sync-loader')
        ->assertSuccessful();

    $content = File::get($this->targetPath);

    expect($content)->toContain('carousel_controller.js');
    expect($content)->toContain('chart_controller.js');
    expect($content)->toContain('file_upload_controller.js');
    expect($content)->toContain('input_mask_controller.js');
    expect($content)->toContain('map_controller.js');
    expect($content)->toContain('rich_text_controller.js');
    expect($content)->toContain('timeago_controller.js');
    expect($content)->toContain('toast_controller.js');
    expect($content)->toContain('toaster_controller.js');
    expect($content)->toContain('tooltip_controller.js');
});

it('is idempotent', function () {
    $this->artisan('hotwire:sync-loader')->assertSuccessful();
    $first = File::get($this->targetPath);

    $this->artisan('hotwire:sync-loader')
        ->assertSuccessful()
        ->expectsOutput('Loader index already up to date.');

    expect(File::get($this->targetPath))->toBe($first);
});
