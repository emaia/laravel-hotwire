<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

beforeEach(function () {
    $this->targetDir = resource_path('js/controllers');

    File::deleteDirectory($this->targetDir);

    $baseDir = realpath(__DIR__.'/../../resources/js/controllers');
    $files = collect(Finder::create()->files()->name('*_controller.js')->name('*_controller.ts')->in($baseDir));

    $this->allControllerNames = $files->mapWithKeys(function ($f) {
        $name = preg_replace('/_controller\.(js|ts)$/', '', $f->getFilename());
        $relativeDir = trim(str_replace('\\', '/', $f->getRelativePath()), '/');
        $label = $relativeDir !== '' ? "[{$relativeDir}] {$name}" : $name;

        return [$name => ['relative_dir' => $relativeDir, 'label' => $label]];
    })
        ->sortBy(fn ($v, $name) => $v['relative_dir'].$name)
        ->mapWithKeys(fn ($v, $name) => [$name => $v['label']])
        ->all();
});

afterEach(function () {
    File::deleteDirectory($this->targetDir);
});

it('lists available controllers', function () {
    $this->artisan('hwc:controllers --list --no-interaction')
        ->assertSuccessful();
});

it('shows interactive multiselect when no arguments given', function () {
    $this->artisan('hwc:controllers')
        ->expectsChoice('Which controllers would you like to publish?', ['modal'], $this->allControllerNames)
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/dialog/modal_controller.js'))->toBeTrue();
});

it('shows no selection message when multiselect returns empty', function () {
    $this->artisan('hwc:controllers')
        ->expectsChoice('Which controllers would you like to publish?', [], $this->allControllerNames)
        ->assertSuccessful();

    expect(File::isDirectory($this->targetDir))->toBeFalse();
});

it('publishes a controller by name', function () {
    $this->artisan('hwc:controllers', ['controllers' => ['modal']])
        ->assertSuccessful();

    $published = $this->targetDir.'/dialog/modal_controller.js';

    expect(File::exists($published))->toBeTrue();

    $source = realpath(__DIR__.'/../../resources/js/controllers/dialog/modal_controller.js');

    expect(File::get($published))->toBe(File::get($source));
});

it('publishes all controllers with --all', function () {
    $this->artisan('hwc:controllers', ['--all' => true])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/dialog/modal_controller.js'))->toBeTrue();
});

it('warns when controller name is not found', function () {
    $this->artisan('hwc:controllers', ['controllers' => ['nonexistent']])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/nonexistent'))->toBeFalse();
});

it('skips when controller is already up to date', function () {
    $this->artisan('hwc:controllers', ['controllers' => ['modal']]);

    $this->artisan('hwc:controllers', ['controllers' => ['modal']])
        ->assertSuccessful();
});

it('warns when controller exists and differs without --force in non-interactive mode', function () {
    $this->artisan('hwc:controllers', ['controllers' => ['modal']]);

    $published = $this->targetDir.'/dialog/modal_controller.js';
    File::put($published, '// modified');

    $this->artisan('hwc:controllers modal --no-interaction')
        ->assertSuccessful();

    expect(File::get($published))->toBe('// modified');
});

it('prompts for confirmation when controller differs in interactive mode', function () {
    $this->artisan('hwc:controllers', ['controllers' => ['modal']]);

    $published = $this->targetDir.'/dialog/modal_controller.js';
    File::put($published, '// modified');

    $this->artisan('hwc:controllers', ['controllers' => ['modal']])
        ->expectsConfirmation(
            'Controller "modal" already exists and differs from the package version. Overwrite?',
            'no',
        )
        ->assertSuccessful();

    expect(File::get($published))->toBe('// modified');
});

it('overwrites when user confirms in interactive mode', function () {
    $this->artisan('hwc:controllers', ['controllers' => ['modal']]);

    $published = $this->targetDir.'/dialog/modal_controller.js';
    File::put($published, '// modified');

    $this->artisan('hwc:controllers', ['controllers' => ['modal']])
        ->expectsConfirmation(
            'Controller "modal" already exists and differs from the package version. Overwrite?',
            'yes',
        )
        ->assertSuccessful();

    $source = realpath(__DIR__.'/../../resources/js/controllers/dialog/modal_controller.js');

    expect(File::get($published))->toBe(File::get($source));
});

it('overwrites when controller exists and --force is used', function () {
    $this->artisan('hwc:controllers', ['controllers' => ['modal']]);

    $published = $this->targetDir.'/dialog/modal_controller.js';
    File::put($published, '// modified');

    $this->artisan('hwc:controllers', ['controllers' => ['modal'], '--force' => true])
        ->assertSuccessful();

    $source = realpath(__DIR__.'/../../resources/js/controllers/dialog/modal_controller.js');

    expect(File::get($published))->toBe(File::get($source));
});

it('preserves directory structure when publishing', function () {
    $this->artisan('hwc:controllers', ['controllers' => ['modal']]);

    expect(File::isDirectory($this->targetDir.'/dialog'))->toBeTrue();
    expect(File::exists($this->targetDir.'/dialog/modal_controller.js'))->toBeTrue();
});

it('detects missing file in target directory as not up to date', function () {
    $this->artisan('hwc:controllers', ['controllers' => ['modal']]);

    // Remove the file but keep the directory
    File::delete($this->targetDir.'/dialog/modal_controller.js');

    $this->artisan('hwc:controllers', ['controllers' => ['modal']])
        ->expectsConfirmation(
            'Controller "modal" already exists and differs from the package version. Overwrite?',
            'yes',
        )
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/dialog/modal_controller.js'))->toBeTrue();
});
