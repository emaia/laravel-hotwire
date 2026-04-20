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

        $key = $relativeDir === '' ? $name : "$relativeDir/$name";

        return [$key => $key];
    })->sort()->all();
});

afterEach(function () {
    File::deleteDirectory($this->targetDir);
});

// --- Helpers ---

function sourceFor(string $key): string
{
    $base = realpath(__DIR__.'/../../resources/js/controllers');

    foreach (['.js', '.ts'] as $ext) {
        $candidate = "{$base}/{$key}_controller{$ext}";
        if (file_exists($candidate)) {
            return $candidate;
        }
    }

    throw new RuntimeException("No source file for {$key}");
}

function targetFor(string $baseDir, string $key): string
{
    $source = sourceFor($key);
    $ext = pathinfo($source, PATHINFO_EXTENSION);

    return "{$baseDir}/{$key}_controller.{$ext}";
}

// --- --list ---

it('lists available controllers', function () {
    $this->artisan('hotwire:controllers --list --no-interaction')
        ->assertSuccessful();
});

// --- Interactive mode ---

it('shows interactive multiselect when no arguments given', function () {
    $this->artisan('hotwire:controllers')
        ->expectsChoice('Which controllers would you like to publish?', ['dialog'], $this->allControllerOptions)
        ->assertSuccessful();

    expect(File::exists(targetFor($this->targetDir, 'dialog')))->toBeTrue();
});

it('shows no selection message when multiselect returns empty', function () {
    $this->artisan('hotwire:controllers')
        ->expectsChoice('Which controllers would you like to publish?', [], $this->allControllerOptions)
        ->assertSuccessful();

    expect(File::isDirectory($this->targetDir))->toBeFalse();
});

// --- Substrate namespace argument ---

it('publishes all controllers in a substrate namespace', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['turbo']])
        ->assertSuccessful();

    expect(File::exists(targetFor($this->targetDir, 'turbo/progress')))->toBeTrue();
});

it('publishes only controllers within the requested substrate', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['turbo']])
        ->assertSuccessful();

    expect(File::isDirectory($this->targetDir.'/optimistic'))->toBeFalse()
        ->and(File::exists(targetFor($this->targetDir, 'dialog')))->toBeFalse();
});

// --- Top-level and substrate/name notation ---

it('publishes a specific top-level controller by name', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['autoselect']])
        ->assertSuccessful();

    $published = targetFor($this->targetDir, 'autoselect');
    $source = sourceFor('autoselect');

    expect(File::exists($published))->toBeTrue()
        ->and(File::get($published))->toBe(File::get($source));
});

it('publishes a specific substrate controller using substrate/name notation', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['turbo/progress']])
        ->assertSuccessful();

    $published = targetFor($this->targetDir, 'turbo/progress');
    $source = sourceFor('turbo/progress');

    expect(File::exists($published))->toBeTrue()
        ->and(File::get($published))->toBe(File::get($source));
});

it('publishes only the requested controller, not the entire substrate', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['turbo/progress']])
        ->assertSuccessful();

    expect(File::exists(targetFor($this->targetDir, 'turbo/progress')))->toBeTrue()
        ->and(File::exists(targetFor($this->targetDir, 'turbo/polling')))->toBeFalse();
});

it('publishes multiple controllers with mixed notation', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['autoselect', 'turbo/progress']])
        ->assertSuccessful();

    expect(File::exists(targetFor($this->targetDir, 'autoselect')))->toBeTrue()
        ->and(File::exists(targetFor($this->targetDir, 'turbo/progress')))->toBeTrue();
});

it('publishes all controllers with --all', function () {
    $this->artisan('hotwire:controllers', ['--all' => true])
        ->assertSuccessful();

    expect(File::exists(targetFor($this->targetDir, 'dialog')))->toBeTrue()
        ->and(File::exists(targetFor($this->targetDir, 'autoselect')))->toBeTrue()
        ->and(File::exists(targetFor($this->targetDir, 'turbo/progress')))->toBeTrue();
});

// --- Error cases ---

it('warns when namespace does not exist', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['nonexistent']])
        ->assertSuccessful();

    expect(File::isDirectory($this->targetDir.'/nonexistent'))->toBeFalse();
});

it('warns when specific controller does not exist within a substrate', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['turbo/nonexistent']])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/turbo/nonexistent_controller.js'))->toBeFalse();
});

// --- Up to date / overwrite ---

it('skips when controller is already up to date', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog']]);

    $this->artisan('hotwire:controllers', ['controllers' => ['dialog']])
        ->assertSuccessful();
});

it('warns when controller exists and differs without --force in non-interactive mode', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog']]);

    $published = targetFor($this->targetDir, 'dialog');
    File::put($published, '// modified');

    $this->artisan('hotwire:controllers dialog --no-interaction')
        ->assertSuccessful();

    expect(File::get($published))->toBe('// modified');
});

it('prompts for confirmation when controller differs in interactive mode', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog']]);

    $published = targetFor($this->targetDir, 'dialog');
    File::put($published, '// modified');

    $this->artisan('hotwire:controllers', ['controllers' => ['dialog']])
        ->expectsConfirmation(
            'Controller "dialog" already exists and differs from the package version. Overwrite?',
            'no',
        )
        ->assertSuccessful();

    expect(File::get($published))->toBe('// modified');
});

it('overwrites when user confirms in interactive mode', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog']]);

    $published = targetFor($this->targetDir, 'dialog');
    File::put($published, '// modified');

    $this->artisan('hotwire:controllers', ['controllers' => ['dialog']])
        ->expectsConfirmation(
            'Controller "dialog" already exists and differs from the package version. Overwrite?',
            'yes',
        )
        ->assertSuccessful();

    $source = sourceFor('dialog');
    expect(File::get($published))->toBe(File::get($source));
});

it('overwrites when controller exists and --force is used', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog']]);

    $published = targetFor($this->targetDir, 'dialog');
    File::put($published, '// modified');

    $this->artisan('hotwire:controllers', ['controllers' => ['dialog'], '--force' => true])
        ->assertSuccessful();

    $source = sourceFor('dialog');
    expect(File::get($published))->toBe(File::get($source));
});

// --- Directory structure ---

it('publishes top-level controllers flat', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog']]);

    expect(File::isDirectory($this->targetDir))->toBeTrue()
        ->and(File::exists($this->targetDir.'/dialog_controller.js'))->toBeTrue()
        ->and(File::isDirectory($this->targetDir.'/dialog'))->toBeFalse();
});

it('preserves substrate directory structure when publishing', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['turbo/progress']]);

    expect(File::isDirectory($this->targetDir.'/turbo'))->toBeTrue()
        ->and(File::exists($this->targetDir.'/turbo/progress_controller.js'))->toBeTrue();
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
    $this->artisan('hotwire:controllers', ['controllers' => ['dialog']]);

    $published = targetFor($this->targetDir, 'dialog');
    File::delete($published);

    $this->artisan('hotwire:controllers', ['controllers' => ['dialog']])
        ->assertSuccessful();

    expect(File::exists($published))->toBeTrue();
});
