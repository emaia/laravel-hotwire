<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->targetDir = resource_path('js/controllers');
    $this->viewsDir = resource_path('views');
    $this->packageJsonPath = base_path('package.json');
    $this->originalPackageJson = File::exists($this->packageJsonPath)
        ? File::get($this->packageJsonPath)
        : null;

    File::deleteDirectory($this->targetDir);
    File::deleteDirectory($this->viewsDir);
    File::ensureDirectoryExists($this->viewsDir);
});

afterEach(function () {
    File::deleteDirectory($this->targetDir);
    File::deleteDirectory($this->viewsDir);

    if ($this->originalPackageJson !== null) {
        File::put($this->packageJsonPath, $this->originalPackageJson);
    } elseif (File::exists($this->packageJsonPath)) {
        File::delete($this->packageJsonPath);
    }
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
    if (str_contains($identifier, '--')) {
        [$dir, $name] = explode('--', $identifier, 2);
    } else {
        $dir = '';
        $name = $identifier;
    }

    $name = str_replace('-', '_', $name);
    $base = realpath(__DIR__.'/../../resources/js/controllers');
    $searchBase = $dir === '' ? $base : "{$base}/{$dir}";
    $source = null;

    foreach (['.js', '.ts'] as $ext) {
        $candidate = "{$searchBase}/{$name}_controller{$ext}";
        if (file_exists($candidate)) {
            $source = $candidate;
            break;
        }
    }

    if ($source === null) {
        throw new RuntimeException("Controller source not found for {$identifier}");
    }

    $ext = pathinfo($source, PATHINFO_EXTENSION);
    $target = $dir === ''
        ? "{$targetDir}/{$name}_controller.{$ext}"
        : "{$targetDir}/{$dir}/{$name}_controller.{$ext}";

    File::ensureDirectoryExists(dirname($target));
    File::copy($source, $target);
}

function writePackageJson(array $data): void
{
    File::put(base_path('package.json'), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
}

function readPackageJson(): array
{
    return json_decode(File::get(base_path('package.json')), true);
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
    writeView('page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('modal')
        ->assertExitCode(1);
});

it('detects component with attributes', function () {
    writeView('page.blade.php', '<x-hwc::confirm-dialog title="Delete?" message="Sure?"><x-slot:trigger><button>x</button></x-slot:trigger></x-hwc::confirm-dialog>');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('confirm-dialog')
        ->assertExitCode(1);
});

it('detects components across multiple files', function () {
    writeView('a.blade.php', '<x-hwc::modal />');
    writeView('b.blade.php', '<x-hwc::confirm-dialog title="x" />');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(1)
        ->and($output)->toContain('<x-hwc::modal>')
        ->and($output)->toContain('<x-hwc::confirm-dialog>');
});

it('deduplicates components used in multiple files', function () {
    writeView('a.blade.php', '<x-hwc::modal />');
    writeView('b.blade.php', '<x-hwc::modal />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();
    expect(substr_count($output, 'x-hwc::modal'))->toBe(1);
});

it('respects custom prefix', function () {
    config()->set('hotwire.prefix', 'h');
    writeView('page.blade.php', '<x-h::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('modal')
        ->assertExitCode(1);
});

it('detects components using hotwire:: alias', function () {
    writeView('page.blade.php', '<x-hotwire::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('modal')
        ->assertExitCode(1);
});

it('detects both hwc:: and hotwire:: prefixes in the same codebase', function () {
    writeView('a.blade.php', '<x-hwc::modal />');
    writeView('b.blade.php', '<x-hotwire::confirm-dialog title="x" />');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(1)
        ->and($output)->toContain('<x-hwc::modal>')
        ->and($output)->toContain('<x-hwc::confirm-dialog>');
});

it('detects hotwire:: alias when a custom prefix is set', function () {
    config()->set('hotwire.prefix', 'h');
    writeView('page.blade.php', '<x-hotwire::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('modal')
        ->assertExitCode(1);
});

it('ignores components from other packages', function () {
    writeView('page.blade.php', '<x-jetstream-button /><x-ui-card />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('No Hotwire components found')
        ->assertSuccessful();
});

// --- Status reporting ---

it('shows not published when controller is missing', function () {
    writeView('page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('not published')
        ->assertExitCode(1);
});

it('shows up to date when controller matches package version', function () {
    publishController('modal', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('up to date')
        ->assertSuccessful();
});

it('shows outdated when controller differs from package version', function () {
    $target = $this->targetDir.'/modal_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, '// modified');
    writeView('page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('outdated')
        ->assertExitCode(1);
});

it('shows which component requires each controller', function () {
    writeView('page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('x-hwc::modal')
        ->assertExitCode(1);
});

it('shows dash for component without controller dependency', function () {
    writeView('page.blade.php', '<x-hwc::loader />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('No controllers required')
        ->assertSuccessful();
});

// --- Exit code ---

it('exits with 0 when all controllers are up to date', function () {
    publishController('modal', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->assertExitCode(0);
});

it('exits with 1 when a controller is not published', function () {
    writeView('page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->assertExitCode(1);
});

it('exits with 1 when a controller is outdated', function () {
    $target = $this->targetDir.'/modal_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, '// modified');
    writeView('page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->assertExitCode(1);
});

// --- --fix flag ---

it('publishes missing controllers with --fix', function () {
    writeView('page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/modal_controller.js'))->toBeTrue();
});

it('updates outdated controllers with --fix', function () {
    $target = $this->targetDir.'/modal_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, '// modified');
    writeView('page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    $source = realpath(__DIR__.'/../../resources/js/controllers/modal_controller.js');
    expect(File::hash($target))->toBe(File::hash($source));
});

// --- TypeScript (.ts) controllers ---

it('shows not published for a ts controller', function () {
    writeView('page.blade.php', '<x-hwc::timeago :datetime="now()" />');

    $exitCode = Artisan::call('hotwire:check', ['--no-interaction' => true]);
    $output = Artisan::output();

    expect($output)->toContain('timeago');
    expect($output)->toContain('not published');
    expect($exitCode)->toBe(1);
});

it('shows up to date when ts controller matches package version', function () {
    publishController('timeago', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::timeago :datetime="now()" />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('up to date')
        ->assertSuccessful();
});

it('shows outdated when ts controller differs from package version', function () {
    $target = $this->targetDir.'/timeago_controller.ts';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, '// modified');
    writeView('page.blade.php', '<x-hwc::timeago :datetime="now()" />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('outdated')
        ->assertExitCode(1);
});

it('publishes missing ts controllers with --fix', function () {
    writeView('page.blade.php', '<x-hwc::timeago :datetime="now()" />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/timeago_controller.ts'))->toBeTrue();
});

it('updates outdated ts controllers with --fix', function () {
    $target = $this->targetDir.'/timeago_controller.ts';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, '// modified');
    writeView('page.blade.php', '<x-hwc::timeago :datetime="now()" />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    $source = realpath(__DIR__.'/../../resources/js/controllers/timeago_controller.ts');
    expect(File::hash($target))->toBe(File::hash($source));
});

// --- --path option ---

it('accepts custom path to scan', function () {
    $customDir = resource_path('views/custom');
    File::ensureDirectoryExists($customDir);
    File::put($customDir.'/page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check', ['--path' => [resource_path('views/custom')], '--no-interaction' => true])
        ->expectsOutputToContain('modal')
        ->assertExitCode(1);
});

it('reports no components found when custom path has no blade files', function () {
    $customDir = resource_path('views/empty');
    File::ensureDirectoryExists($customDir);

    $this->artisan('hotwire:check', ['--path' => [resource_path('views/empty')], '--no-interaction' => true])
        ->expectsOutputToContain('No Hotwire components found')
        ->assertSuccessful();
});

// --- NPM dependencies ---

it('lists required npm dependencies for used controllers', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('Required npm dependencies')
        ->expectsOutputToContain('@emaia/sonner')
        ->assertExitCode(1);
});

it('marks dependency as present when listed in dependencies', function () {
    writePackageJson(['name' => 'app', 'dependencies' => ['@emaia/sonner' => '^2.1.0']]);
    publishController('toast', $this->targetDir);
    publishController('toaster', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('missing from package.json')
        ->assertSuccessful();
});

it('marks dependency as present when listed in devDependencies', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => ['@emaia/sonner' => '^2.1.0']]);
    publishController('toast', $this->targetDir);
    publishController('toaster', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('missing from package.json')
        ->assertSuccessful();
});

it('marks dependency as missing when absent from package.json', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    publishController('toast', $this->targetDir);
    publishController('toaster', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('@emaia/sonner')
        ->expectsOutputToContain('missing from package.json')
        ->assertExitCode(1);
});

it('normalizes scoped subpath imports', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => ['@emaia/sonner' => '^2.1.0']]);
    publishController('toast', $this->targetDir);
    publishController('toaster', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('@emaia/sonner/vanilla')
        ->assertSuccessful();
});

it('ignores core dependencies', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('@hotwired/stimulus')
        ->doesntExpectOutputToContain('@hotwired/turbo')
        ->doesntExpectOutputToContain('@emaia/stimulus-dynamic-loader');
});

it('deduplicates dependencies used by multiple controllers', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect(substr_count($output, '@emaia/sonner'))->toBe(1);
});

it('exits with 1 when a dependency is missing', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    publishController('toast', $this->targetDir);
    publishController('toaster', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --no-interaction')
        ->assertExitCode(1);
});

it('adds missing npm dependencies to devDependencies with --fix', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    $json = readPackageJson();
    expect($json['devDependencies'])->toHaveKey('@emaia/sonner');
});

it('does not duplicate a dependency already present when --fix runs', function () {
    writePackageJson(['name' => 'app', 'dependencies' => ['@emaia/sonner' => '^2.1.0'], 'devDependencies' => []]);
    publishController('toast', $this->targetDir);
    publishController('toaster', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    $json = readPackageJson();
    expect($json['dependencies'])->toHaveKey('@emaia/sonner');
    expect($json['devDependencies'] ?? [])->not->toHaveKey('@emaia/sonner');
});

it('warns and skips npm check when package.json does not exist', function () {
    if (File::exists($this->packageJsonPath)) {
        File::delete($this->packageJsonPath);
    }
    publishController('toast', $this->targetDir);
    publishController('toaster', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('package.json not found')
        ->assertSuccessful();
});

it('reports npm deps even when controllers are not yet published', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('@emaia/sonner')
        ->assertExitCode(1);
});
