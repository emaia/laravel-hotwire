<?php

use Emaia\LaravelHotwire\Support\ControllerImports;
use Emaia\LaravelHotwire\Support\PackageInstaller;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;

class FakePackageInstaller extends PackageInstaller
{
    /** @var string[] */
    public array $installed = [];

    public function __construct(
        public string $manager = 'bun',
        public int $exitCode = 0,
    ) {}

    public function detect(Filesystem $files): string
    {
        return $this->manager;
    }

    public function install(string $manager, Command $command): int
    {
        $this->installed[] = $manager;

        return $this->exitCode;
    }
}

beforeEach(/**
 * @throws FileNotFoundException
 */ function () {
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
    $path = resource_path("views/$name");
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
    $searchBase = $dir === '' ? $base : "$base/$dir";
    $source = null;

    foreach (['.js', '.ts'] as $ext) {
        $candidate = "$searchBase/{$name}_controller$ext";
        if (file_exists($candidate)) {
            $source = $candidate;
            break;
        }
    }

    if ($source === null) {
        throw new RuntimeException("Controller source not found for $identifier");
    }

    $ext = pathinfo($source, PATHINFO_EXTENSION);
    $target = $dir === ''
        ? "$targetDir/{$name}_controller.$ext"
        : "$targetDir/$dir/{$name}_controller.$ext";

    File::ensureDirectoryExists(dirname($target));
    File::copy($source, $target);

    // Mirror PublishControllersCommand: also copy shared deps the controller imports.
    $imports = app(ControllerImports::class);
    foreach ($imports->sharedDependencies($source, $base) as $depSource) {
        $depTarget = $imports->targetPath($depSource, $base, $targetDir);
        File::ensureDirectoryExists(dirname($depTarget));
        File::copy($depSource, $depTarget);
    }
}

function writePackageJson(array $data): void
{
    File::put(base_path('package.json'), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
}

function readPackageJson(): array
{
    return json_decode(File::get(base_path('package.json')), true);
}

function fakePackageInstaller(string $manager = 'bun', int $exitCode = 0): FakePackageInstaller
{
    $fake = new FakePackageInstaller($manager, $exitCode);
    app()->instance(PackageInstaller::class, $fake);

    return $fake;
}

// --- Basic ---

it('runs successfully with no views', function () {
    $this->artisan('hotwire:check --no-interaction')
        ->assertSuccessful();
});

it('reports all ok when no hotwire components used', function () {
    writeView('page.blade.php', '<div>Hello world</div>');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('No Hotwire components or controllers found')
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
    writeView('page.blade.php', '<x-hwc::confirm-dialog title="Delete?" message="Sure?"><button>x</button></x-hwc::confirm-dialog>');

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
        ->expectsOutputToContain('No Hotwire components or controllers found')
        ->assertSuccessful();
});

// --- Standalone controller detection ---

it('detects standalone controller via data-controller attribute', function () {
    writeView('page.blade.php', '<div data-controller="timeago"></div>');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(1)
        ->and($output)->toContain('timeago')
        ->and($output)->toContain('used by standalone');
});

it('detects multiple controllers via data-controller attribute', function () {
    writeView('page.blade.php', '<section data-controller="timeago modal">x</section>');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(1)
        ->and($output)->toContain('timeago')
        ->and($output)->toContain('modal');
});

it('detects standalone controller via stimulus_controller()', function () {
    writeView('page.blade.php', '{{ stimulus_controller(\'timeago\', [\'datetime\' => now()]) }}');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(1)
        ->and($output)->toContain('timeago')
        ->and($output)->toContain('used by standalone');
});

it('detects standalone controller via stimulus()->controller()', function () {
    writeView('page.blade.php', '{{ stimulus()->controller(\'tooltip\')->action(\'tooltip\', \'show\', \'mouseenter\') }}');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(1)
        ->and($output)->toContain('tooltip')
        ->and($output)->toContain('used by standalone');
});

it('detects multiple via stimulus()->controllers()', function () {
    writeView('page.blade.php', '{{ stimulus()->controllers(\'modal\', \'confirm-dialog\') }}');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(1)
        ->and($output)->toContain('modal')
        ->and($output)->toContain('confirm-dialog');
});

it('detects chained stimulus()->controller()->controller()', function () {
    writeView('page.blade.php', "{{ stimulus()->controller('modal')->controller('tooltip') }}");

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($output)->toContain('modal')
        ->and($output)->toContain('tooltip');
});

it('does not double-report a controller used by both a component and standalone', function () {
    publishController('modal', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::modal>x</x-hwc::modal><div data-controller="modal"></div>');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    // Reported once via the component, never again as standalone.
    expect($output)->toContain('modal')
        ->and(substr_count($output, 'used by standalone'))->toBe(0);
});

it('detects standalone controller via stimulus_action()', function () {
    writeView('page.blade.php', '{{ stimulus_action(\'carousel\', \'next\') }}');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(1)
        ->and($output)->toContain('carousel')
        ->and($output)->toContain('used by standalone');
});

it('detects standalone controller via stimulus_target()', function () {
    writeView('page.blade.php', '{{ stimulus_target(\'carousel\', \'viewport\') }}');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(1)
        ->and($output)->toContain('carousel')
        ->and($output)->toContain('used by standalone');
});

it('ignores user-defined controller not in package registry', function () {
    writeView('page.blade.php', '<div data-controller="my-custom-thing"></div>');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('my-custom-thing')
        ->assertSuccessful();
});

it('ignores data-controller inside Blade comments', function () {
    writeView('page.blade.php', '{{-- <div data-controller="timeago"> --}}<p>real content</p>');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('timeago')
        ->assertSuccessful();
});

it('ignores a hotwire component inside Blade comments', function () {
    writeView('page.blade.php', '{{-- <x-hwc::carousel slide-size="80%"> --}}{{-- </x-hwc::carousel> --}}<p>real content</p>');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('No Hotwire components or controllers found')
        ->assertSuccessful();
});

it('ignores data-controller inside script tags', function () {
    writeView('page.blade.php', '<script>const el = \'<div data-controller="timeago">\';</script><p>real</p>');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('timeago')
        ->assertSuccessful();
});

it('deduplicates standalone controller used across multiple files', function () {
    writeView('a.blade.php', '<div data-controller="timeago"></div>');
    writeView('b.blade.php', '<div data-controller="timeago"></div>');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect(substr_count($output, 'timeago'))->toBe(1);
});

it('publishes missing standalone controller with --fix', function () {
    writeView('page.blade.php', '<div data-controller="timeago"></div>');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/timeago_controller.js'))->toBeTrue();
});

it('reports npm deps for standalone controllers', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<div data-controller="toast"></div>');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('Required npm dependencies')
        ->expectsOutputToContain('@emaia/sonner')
        ->assertExitCode(1);
});

it('reports not published for standalone controller when file is missing', function () {
    writeView('page.blade.php', '<div data-controller="timeago"></div>');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('not published')
        ->assertExitCode(1);
});

it('reports up to date for standalone controller when file matches', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => ['date-fns' => '^4.1.0']]);
    publishController('timeago', $this->targetDir);
    writeView('page.blade.php', '<div data-controller="timeago"></div>');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('up to date')
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
    File::put($target, "// @hotwire-package\n// modified");
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
    writeView('page.blade.php', '<x-hwc::spinner />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('No controllers required')
        ->assertSuccessful();
});

it('groups problem lines under a "Needs attention" heading at the end of the output', function () {
    writeView('page.blade.php', '<x-hwc::modal />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($output)->toContain('Needs attention:');

    // The header should appear AFTER the per-view scan output and BEFORE the
    // summary count, so the user can read it without scrolling.
    $needsAttentionPos = strpos($output, 'Needs attention:');
    $modalProblemPos = strpos($output, 'not published');
    $summaryPos = strpos($output, 'controller(s) need attention');

    expect($needsAttentionPos)->toBeLessThan($modalProblemPos);
    expect($modalProblemPos)->toBeLessThan($summaryPos);
});

it('does not print the "Needs attention" heading when everything is up to date', function () {
    publishController('modal', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::modal />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($output)->not->toContain('Needs attention:');
});

it('sorts scanned components alphabetically', function () {
    writeView('a.blade.php', '<x-hwc::modal /><x-hwc::carousel /><x-hwc::dropdown />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    $carouselPos = strpos($output, '<x-hwc::carousel>');
    $dropdownPos = strpos($output, '<x-hwc::dropdown>');
    $modalPos = strpos($output, '<x-hwc::modal>');

    expect($carouselPos)->toBeLessThan($dropdownPos);
    expect($dropdownPos)->toBeLessThan($modalPos);
});

it('sorts the Needs attention block alphabetically', function () {
    writeView('a.blade.php', '<x-hwc::modal /><x-hwc::carousel /><x-hwc::dropdown />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    $needsAttentionPos = strpos($output, 'Needs attention:');
    $tail = substr($output, $needsAttentionPos);

    // Match the entry-line pattern "  IDENTIFIER  not published" to avoid
    // collisions with "(required by IDENTIFIER)" suffixes on shared dep lines.
    $carouselPos = strpos($tail, '  carousel  not published');
    $dropdownPos = strpos($tail, '  dropdown  not published');
    $modalPos = strpos($tail, '  modal  not published');

    expect($carouselPos)->toBeLessThan($dropdownPos);
    expect($dropdownPos)->toBeLessThan($modalPos);
});

it('groups OK output as components -> standalones -> helpers', function () {
    publishController('dropdown', $this->targetDir);
    publishController('disclosure', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::dropdown /><div data-controller="disclosure"></div>');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    $componentPos = strpos($output, '  dropdown  up to date  ');
    $standalonePos = strpos($output, '  disclosure  up to date  ');
    $helperPos = strpos($output, '  _transition.js  up to date  ');

    expect($componentPos)->toBeLessThan($standalonePos);
    expect($standalonePos)->toBeLessThan($helperPos);
});

it('groups <x-hwc::*> "no controllers required" entries after component controllers', function () {
    publishController('modal', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::modal /><x-hwc::spinner /><x-hwc::field />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    $modalPos = strpos($output, '  modal  up to date  ');
    $fieldPos = strpos($output, '<x-hwc::field>  No controllers required');
    $spinnerPos = strpos($output, '<x-hwc::spinner>  No controllers required');

    expect($modalPos)->toBeLessThan($fieldPos);
    expect($fieldPos)->toBeLessThan($spinnerPos);
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
    File::put($target, "// @hotwire-package\n// modified");
    writeView('page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    $source = realpath(__DIR__.'/../../resources/js/controllers/modal_controller.js');
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
        ->expectsOutputToContain('No Hotwire components or controllers found')
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

// --- Shared controller dependencies ---

function depSource(string $name): string
{
    return (string) realpath(__DIR__."/../../resources/js/controllers/$name");
}

it('reports a missing shared dependency as not published', function () {
    publishController('file-preserve', $this->targetDir);
    publishController('reset-files', $this->targetDir);
    File::delete($this->targetDir.'/_form_errors.js'); // simulate missing shared dep
    writeView('page.blade.php', '<x-hwc::file name="avatar" />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('_form_errors.js  not published')
        ->assertExitCode(1);
});

it('marks a shared dependency up to date when present', function () {
    publishController('file-preserve', $this->targetDir);
    publishController('reset-files', $this->targetDir);
    File::copy(depSource('_form_errors.js'), $this->targetDir.'/_form_errors.js');
    writeView('page.blade.php', '<x-hwc::file name="avatar" />');

    $this->artisan('hotwire:check --no-interaction')
        ->assertSuccessful();
});

it('reports a shared dependency as outdated when it differs', function () {
    publishController('file-preserve', $this->targetDir);
    publishController('reset-files', $this->targetDir);
    File::put($this->targetDir.'/_form_errors.js', "// @hotwire-package\n// modified");
    writeView('page.blade.php', '<x-hwc::file name="avatar" />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('_form_errors.js  outdated')
        ->assertExitCode(1);
});

it('publishes a missing shared dependency with --fix', function () {
    publishController('file-preserve', $this->targetDir);
    publishController('reset-files', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::file name="avatar" />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    expect(File::hash($this->targetDir.'/_form_errors.js'))
        ->toBe(File::hash(depSource('_form_errors.js')));
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

it('does not run package manager install in non-interactive fix mode by default', function () {
    $installer = fakePackageInstaller('bun');
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->expectsOutputToContain('Run your package manager install command')
        ->assertSuccessful();

    expect($installer->installed)->toBe([]);
});

it('runs package manager install when requested explicitly', function () {
    $installer = fakePackageInstaller('bun');
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --fix --install --no-interaction')
        ->expectsOutputToContain('Running bun install')
        ->expectsOutputToContain('bun install completed')
        ->assertSuccessful();

    expect($installer->installed)->toBe(['bun']);
});

it('prompts to run package manager install after interactive fix adds dependencies', function () {
    $installer = fakePackageInstaller('pnpm');
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check')
        ->expectsConfirmation('Publish missing/outdated controllers and add missing npm deps?', 'yes')
        ->expectsConfirmation('Run pnpm install now?', 'yes')
        ->expectsOutputToContain('Running pnpm install')
        ->assertSuccessful();

    expect($installer->installed)->toBe(['pnpm']);
});

it('does not run package manager install when no dependencies were added', function () {
    $installer = fakePackageInstaller('bun');
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check --fix --install --no-interaction')
        ->doesntExpectOutputToContain('Running bun install')
        ->assertSuccessful();

    expect($installer->installed)->toBe([]);
});

it('fails when requested package manager install fails', function () {
    $installer = fakePackageInstaller('npm', 1);
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --fix --install --no-interaction')
        ->expectsOutputToContain('npm install failed')
        ->assertFailed();

    expect($installer->installed)->toBe(['npm']);
});

it('does not duplicate a dependency already present when --fix runs', function () {
    writePackageJson(['name' => 'app', 'dependencies' => ['@emaia/sonner' => '^2.1.0'], 'devDependencies' => []]);
    publishController('toast', $this->targetDir);
    publishController('toaster', $this->targetDir);
    writeView('page.blade.php', '<x-hwc::flash-message />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    $json = readPackageJson();
    expect($json['dependencies'])->toHaveKey('@emaia/sonner')
        ->and($json['devDependencies'] ?? [])->not->toHaveKey('@emaia/sonner');
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

// --- Package marker guard ---

it('refuses to overwrite a user-owned controller when running --fix', function () {
    $target = $this->targetDir.'/modal_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, "// user code, no package marker\nexport default class {}\n");
    writeView('page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->expectsOutputToContain('user-owned')
        ->assertSuccessful();

    expect(File::get($target))->toBe("// user code, no package marker\nexport default class {}\n");
});

it('labels a user-owned divergence as "diverged (user-owned)" and does not act on it with --fix', function () {
    $target = $this->targetDir.'/modal_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, "// user code\nexport default class {}\n");
    writeView('page.blade.php', '<x-hwc::modal />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->expectsOutputToContain('diverged (user-owned)')
        ->doesntExpectOutputToContain('Skipped')
        ->assertSuccessful();

    expect(File::get($target))->toBe("// user code\nexport default class {}\n");
});
