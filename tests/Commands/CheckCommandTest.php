<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->targetDir = resource_path('js/controllers');
    $this->viewsDir = resource_path('views');

    File::deleteDirectory($this->targetDir);
    File::deleteDirectory($this->viewsDir);
    File::ensureDirectoryExists($this->viewsDir);
});

afterEach(function () {
    File::deleteDirectory($this->targetDir);
    File::deleteDirectory($this->viewsDir);
});

// --- Helpers ---

function writeView(string $name, string $content): void
{
    $path = resource_path("views/{$name}");
    File::ensureDirectoryExists(dirname($path));
    File::put($path, $content);
}

function publishController(string $identifier, string $targetDir): void
{
    [$dir, $name] = explode('--', $identifier, 2);
    $name = str_replace('-', '_', $name);
    $source = realpath(__DIR__."/../../resources/js/controllers/{$dir}/{$name}_controller.js");
    $target = "{$targetDir}/{$dir}/{$name}_controller.js";
    File::ensureDirectoryExists(dirname($target));
    File::copy($source, $target);
}

// --- Basic ---

it('runs successfully with no views', function () {
    $this->artisan('hotwire:check --no-interaction')
        ->assertSuccessful();
});

it('reports all ok when no hotwire components used', function () {
    writeView('page.blade.php', '<div>Hello world</div>');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('No Hotwire components found')
        ->assertSuccessful();
});

// --- Detection ---

it('detects component used in a blade file', function () {
    writeView('page.blade.php', '<x-hwc-modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('dialog--modal')
        ->assertExitCode(1); // controller not published → exit 1
});

it('detects component with attributes', function () {
    writeView('page.blade.php', '<x-hwc-confirm title="Delete?" message="Sure?"><x-slot:trigger><button>x</button></x-slot:trigger></x-hwc-confirm>');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('dialog--confirm')
        ->assertExitCode(1); // controller not published → exit 1
});

it('detects components across multiple files', function () {
    writeView('a.blade.php', '<x-hwc-modal />');
    writeView('b.blade.php', '<x-hwc-confirm title="x" />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('dialog--modal')
        ->expectsOutputToContain('dialog--confirm')
        ->assertExitCode(1); // controllers not published → exit 1
});

it('deduplicates components used in multiple files', function () {
    writeView('a.blade.php', '<x-hwc-modal />');
    writeView('b.blade.php', '<x-hwc-modal />');

    // dialog--modal should appear once in output
    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();
    expect(substr_count($output, 'dialog--modal'))->toBe(1);
});

it('respects custom prefix', function () {
    config()->set('hotwire.prefix', 'h');
    writeView('page.blade.php', '<x-h-modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('dialog--modal')
        ->assertExitCode(1); // controller not published → exit 1
});

it('ignores components from other packages', function () {
    writeView('page.blade.php', '<x-jetstream-button /><x-ui-card />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('No Hotwire components found')
        ->assertSuccessful();
});

// --- Status reporting ---

it('shows not published when controller is missing', function () {
    writeView('page.blade.php', '<x-hwc-modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('not published')
        ->assertExitCode(1);
});

it('shows up to date when controller matches package version', function () {
    publishController('dialog--modal', $this->targetDir);
    writeView('page.blade.php', '<x-hwc-modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('up to date')
        ->assertSuccessful();
});

it('shows outdated when controller differs from package version', function () {
    $target = $this->targetDir.'/dialog/modal_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, '// modified');
    writeView('page.blade.php', '<x-hwc-modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('outdated')
        ->assertExitCode(1);
});

it('shows which component requires each controller', function () {
    writeView('page.blade.php', '<x-hwc-modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('x-hwc-modal')
        ->assertExitCode(1); // controller not published → exit 1
});

it('shows dash for component without controller dependency', function () {
    writeView('page.blade.php', '<x-hwc-loader />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('No controllers required')
        ->assertSuccessful();
});

// --- Exit code ---

it('exits with 0 when all controllers are up to date', function () {
    publishController('dialog--modal', $this->targetDir);
    writeView('page.blade.php', '<x-hwc-modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->assertExitCode(0);
});

it('exits with 1 when a controller is not published', function () {
    writeView('page.blade.php', '<x-hwc-modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->assertExitCode(1);
});

it('exits with 1 when a controller is outdated', function () {
    $target = $this->targetDir.'/dialog/modal_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, '// modified');
    writeView('page.blade.php', '<x-hwc-modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->assertExitCode(1);
});

// --- --fix flag ---

it('publishes missing controllers with --fix', function () {
    writeView('page.blade.php', '<x-hwc-modal />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/dialog/modal_controller.js'))->toBeTrue();
});

it('updates outdated controllers with --fix', function () {
    $target = $this->targetDir.'/dialog/modal_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, '// modified');
    writeView('page.blade.php', '<x-hwc-modal />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    $source = realpath(__DIR__.'/../../resources/js/controllers/dialog/modal_controller.js');
    expect(File::hash($target))->toBe(File::hash($source));
});

// --- --path option ---

it('accepts custom path to scan', function () {
    $customDir = resource_path('views/custom');
    File::ensureDirectoryExists($customDir);
    File::put($customDir.'/page.blade.php', '<x-hwc-modal />');

    $this->artisan('hotwire:check --path='.resource_path('views/custom').' --no-interaction')
        ->expectsOutputToContain('dialog--modal')
        ->assertExitCode(1); // controller not published → exit 1
});

it('reports no components found when custom path has no blade files', function () {
    $customDir = resource_path('views/empty');
    File::ensureDirectoryExists($customDir);

    $this->artisan('hotwire:check --path='.resource_path('views/empty').' --no-interaction')
        ->expectsOutputToContain('No Hotwire components found')
        ->assertSuccessful();
});
