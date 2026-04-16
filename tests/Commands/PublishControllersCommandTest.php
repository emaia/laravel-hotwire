<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

beforeEach(function () {
    $this->targetDir = resource_path('js/controllers');

    File::deleteDirectory($this->targetDir);

    $baseDir = realpath(__DIR__.'/../../resources/js/controllers');

    $this->allControllerOptions = collect(
        Finder::create()->files()
            ->name('*_controller.js')
            ->name('*_controller.ts')
            ->in($baseDir)
    )->mapWithKeys(function ($f) {
        $name = preg_replace('/_controller\.(js|ts)$/', '', $f->getFilename());
        $relativeDir = trim(str_replace('\\', '/', $f->getRelativePath()), '/');

        if ($relativeDir === '') {
            return [];
        }

        $key = "$relativeDir/$name";

        return [$key => $key];
    })->sort()->all();
});

afterEach(function () {
    File::deleteDirectory($this->targetDir);
});

// --- --list ---

it('lists available controllers', function () {
    $this->artisan('hotwire:controllers --list --no-interaction')
        ->assertSuccessful();
});

// --- Interactive mode ---

it('shows interactive multiselect when no arguments given', function () {
    $this->artisan('hotwire:controllers')
        ->expectsChoice('Which controllers would you like to publish?', ['dialog/modal'], $this->allControllerOptions)
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/dialog/modal_controller.js'))->toBeTrue();
});

it('shows no selection message when multiselect returns empty', function () {
    $this->artisan('hotwire:controllers')
        ->expectsChoice('Which controllers would you like to publish?', [], $this->allControllerOptions)
        ->assertSuccessful();

    expect(File::isDirectory($this->targetDir))->toBeFalse();
});

// --- Namespace argument ---

it('publishes all controllers in a namespace', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog']])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/dialog/modal_controller.js'))->toBeTrue();
});

it('publishes only controllers within the requested namespace', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog']])
        ->assertSuccessful();

    expect(File::isDirectory($this->targetDir.'/form'))->toBeFalse();
});

// --- namespace/name notation ---

it('publishes a specific controller using namespace/name notation', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['form/autoselect']])
        ->assertSuccessful();

    $published = $this->targetDir.'/form/autoselect_controller.js';
    $source = realpath(__DIR__.'/../../resources/js/controllers/form/autoselect_controller.js');

    expect(File::exists($published))->toBeTrue()
        ->and(File::get($published))->toBe(File::get($source));
});

it('publishes only the requested controller, not the entire namespace', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['form/autoselect']])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/form/autoselect_controller.js'))->toBeTrue()
        ->and(File::exists($this->targetDir.'/form/autosubmit_controller.js'))->toBeFalse();
});

it('publishes multiple controllers with mixed notation', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['form/autoselect', 'dialog/modal']])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/form/autoselect_controller.js'))->toBeTrue()
        ->and(File::exists($this->targetDir.'/dialog/modal_controller.js'))->toBeTrue();
});

it('publishes all controllers with --all', function () {
    $this->artisan('hotwire:controllers', ['--all' => true])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/dialog/modal_controller.js'))->toBeTrue()
        ->and(File::exists($this->targetDir.'/form/autoselect_controller.js'))->toBeTrue();
});

// --- Error cases ---

it('warns when namespace does not exist', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['nonexistent']])
        ->assertSuccessful();

    expect(File::isDirectory($this->targetDir.'/nonexistent'))->toBeFalse();
});

it('warns when specific controller does not exist within a namespace', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['form/nonexistent']])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/form/nonexistent_controller.js'))->toBeFalse();
});

// --- Up to date / overwrite ---

it('skips when controller is already up to date', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog/modal']]);

    $this->artisan('hotwire:controllers', ['controllers' => ['dialog/modal']])
        ->assertSuccessful();
});

it('warns when controller exists and differs without --force in non-interactive mode', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog/modal']]);

    $published = $this->targetDir.'/dialog/modal_controller.js';
    File::put($published, '// modified');

    $this->artisan('hotwire:controllers dialog/modal --no-interaction')
        ->assertSuccessful();

    expect(File::get($published))->toBe('// modified');
});

it('prompts for confirmation when controller differs in interactive mode', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog/modal']]);

    $published = $this->targetDir.'/dialog/modal_controller.js';
    File::put($published, '// modified');

    $this->artisan('hotwire:controllers', ['controllers' => ['dialog/modal']])
        ->expectsConfirmation(
            'Controller "dialog/modal" already exists and differs from the package version. Overwrite?',
            'no',
        )
        ->assertSuccessful();

    expect(File::get($published))->toBe('// modified');
});

it('overwrites when user confirms in interactive mode', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog/modal']]);

    $published = $this->targetDir.'/dialog/modal_controller.js';
    File::put($published, '// modified');

    $this->artisan('hotwire:controllers', ['controllers' => ['dialog/modal']])
        ->expectsConfirmation(
            'Controller "dialog/modal" already exists and differs from the package version. Overwrite?',
            'yes',
        )
        ->assertSuccessful();

    $source = realpath(__DIR__.'/../../resources/js/controllers/dialog/modal_controller.js');
    expect(File::get($published))->toBe(File::get($source));
});

it('overwrites when controller exists and --force is used', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog/modal']]);

    $published = $this->targetDir.'/dialog/modal_controller.js';
    File::put($published, '// modified');

    $this->artisan('hotwire:controllers', ['controllers' => ['dialog/modal'], '--force' => true])
        ->assertSuccessful();

    $source = realpath(__DIR__.'/../../resources/js/controllers/dialog/modal_controller.js');
    expect(File::get($published))->toBe(File::get($source));
});

// --- Directory structure ---

it('preserves directory structure when publishing', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog/modal']]);

    expect(File::isDirectory($this->targetDir.'/dialog'))->toBeTrue()
        ->and(File::exists($this->targetDir.'/dialog/modal_controller.js'))->toBeTrue();
});

// --- Shared dependencies ---

it('publishes shared dependencies alongside controllers that import them', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['optimistic/form']])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/optimistic/form_controller.js'))->toBeTrue()
        ->and(File::exists($this->targetDir.'/optimistic/_dispatch.js'))->toBeTrue();
});

it('does not duplicate shared dependency when publishing multiple controllers from same namespace', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['optimistic/form', 'optimistic/link']])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/optimistic/_dispatch.js'))->toBeTrue()
        ->and(File::exists($this->targetDir.'/optimistic/form_controller.js'))->toBeTrue()
        ->and(File::exists($this->targetDir.'/optimistic/link_controller.js'))->toBeTrue();
});

it('republishes without prompt when file was deleted but directory remains', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog/modal']]);

    File::delete($this->targetDir.'/dialog/modal_controller.js');

    $this->artisan('hotwire:controllers', ['controllers' => ['dialog/modal']])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/dialog/modal_controller.js'))->toBeTrue();
});
