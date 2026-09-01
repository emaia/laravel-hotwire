<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ControllerImports;
use Emaia\LaravelHotwire\Support\LoaderStub;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->appBase = isolateAppPaths();
    $this->targetDir = resource_path('js/controllers');
    $this->viewsDir = resource_path('views');
    $this->packageJsonPath = base_path('package.json');
});

afterEach(function () {
    releaseIsolatedAppPaths($this->appBase);
});

// --- Helpers ---

function writeView(string $name, string $content): void
{
    $path = resource_path("views/$name");
    File::ensureDirectoryExists(dirname($path));
    File::put($path, $content);
}

function shippedPresetImportPath(string $name = 'nova'): string
{
    $source = dirname(__DIR__, 2).'/resources/css';
    $target = base_path('vendor/emaia/laravel-hotwire/resources/css');
    File::ensureDirectoryExists(dirname($target));
    File::copyDirectory($source, $target);

    return "../../vendor/emaia/laravel-hotwire/resources/css/presets/{$name}.css";
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

// --- Basic ---

it('runs successfully with no views', function () {
    $this->artisan('hotwire:check --no-interaction')
        ->assertSuccessful();
});

it('ignores ambiguous local controllers unrelated to the check', function () {
    writeView('page.blade.php', '<div data-controller="modal"></div>');
    File::ensureDirectoryExists($this->targetDir.'/components');
    File::put($this->targetDir.'/foo_controller.js', 'export default class {}');
    File::put($this->targetDir.'/components/foo_controller.js', 'export default class {}');

    $this->artisan('hotwire:check --no-interaction')
        ->assertSuccessful();
});

it('reports ambiguous configured local controllers safely while fixing', function () {
    writeView('page.blade.php', '<div data-controller="modal"></div>');
    File::ensureDirectoryExists($this->targetDir.'/components');
    File::put($this->targetDir.'/foo_controller.js', 'export default class {}');
    File::put($this->targetDir.'/components/foo_controller.js', 'export default class {}');
    config()->set('hotwire.controllers.eager', ['foo']);

    $this->artisan('hotwire:check --fix --no-interaction')
        ->expectsOutputToContain('Controller [foo] is ambiguous')
        ->assertFailed();
});

it('reports all ok when no hotwire components used', function () {
    writeView('page.blade.php', '<div>Hello world</div>');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('No Hotwire components or controllers found')
        ->assertSuccessful();
});

// --- Semantic token contrast ---

// Application CSS is deliberately outside hotwire:check. These regressions prevent a future
// contrast implementation from inferring ownership merely from token names or resources/css paths.

it('does not treat unrelated stylesheets as Hotwire token contracts', function () {
    File::ensureDirectoryExists(resource_path('css/widgets'));
    File::put(resource_path('css/widgets/filament.css'), ':root { --primary: #6366f1 }');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('semantic token contrast')
        ->assertSuccessful();
});

it('does not claim to audit application token overrides', function () {
    File::ensureDirectoryExists(resource_path('css/presets'));
    File::put(resource_path('css/presets/brand.css'), <<<'CSS'
[data-theme="dark"] {
    --primary: oklch(0.8 0 0);
}
CSS);
    File::put(resource_path('css/app.css'), <<<'CSS'
@import "./presets/brand.css";
[data-theme="dark"] {
    --primary-foreground: oklch(0.75 0 0);
}
CSS);

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('semantic token contrast')
        ->assertSuccessful();
});

it('accepts application color syntax and indirection without interpreting it', function () {
    File::ensureDirectoryExists(resource_path('css'));
    File::put(resource_path('css/app.css'), <<<'CSS'
@layer base {
    :root[data-theme='dark'] {
        --background: #fff;
        --foreground: rgb(17 17 17);
        --primary: hsl(239 84% 67%);
        --primary-foreground: var(--brand-on-primary)
    }
}
CSS);

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('ratio unavailable')
        ->assertSuccessful();
});

it('does not rewrite application token values with fix', function () {
    File::ensureDirectoryExists(resource_path('css'));
    $path = resource_path('css/app.css');
    $css = ':root { --foreground: oklch(0.9 0 0); }';
    File::put($path, $css);

    $exit = Artisan::call('hotwire:check --fix --no-interaction');

    expect($exit)->toBe(0)
        ->and(File::get($path))->toBe($css);
});

// --- Selective CSS drift ---

it('reports a visual component not covered by any generated CSS bundle', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --no-interaction')->assertSuccessful();

    $exit = Artisan::call('hotwire:check --no-interaction');

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toContain('<x-hw::badge>', 'not covered by any generated CSS bundle');
});

it('accepts visual coverage from any generated CSS bundle', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --output=resources/css/front.css --no-interaction')->assertSuccessful();
    $this->artisan('hotwire:styles --components=badge --output=resources/css/admin.css --no-interaction')->assertSuccessful();

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('not covered by any generated CSS bundle')
        ->assertSuccessful();
});

it('accepts a complete preset fallback alongside generated CSS bundles', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --no-interaction')->assertSuccessful();
    File::put(resource_path('css/app.css'), '@import "'.shippedPresetImportPath().'";');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('not covered by any generated CSS bundle')
        ->assertSuccessful();
});

it('accepts an unquoted url import of a complete shipped preset', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --no-interaction')->assertSuccessful();
    File::put(resource_path('css/app.css'), '@import url('.shippedPresetImportPath().');');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('not covered by any generated CSS bundle')
        ->assertSuccessful();
});

it('accepts comments as whitespace in a complete preset import', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --no-interaction')->assertSuccessful();
    File::put(resource_path('css/app.css'), '@import /* preset */ url('.shippedPresetImportPath().') /* complete */;');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('not covered by any generated CSS bundle')
        ->assertSuccessful();
});

it('does not treat a complete preset imported into a cascade layer as complete coverage', function (string $layer) {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --no-interaction')->assertSuccessful();
    File::put(resource_path('css/app.css'), '@import "'.shippedPresetImportPath().'" '.$layer.';');

    $exit = Artisan::call('hotwire:check --no-interaction');

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toContain('<x-hw::badge>', 'not covered by any generated CSS bundle');
})->with([
    'anonymous' => 'layer',
    'ascii' => 'layer(hotwire)',
    'unicode' => 'layer(über)',
    'escaped' => 'layer(\\31 foo)',
]);

it('accepts a complete preset import after a UTF-8 BOM', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --no-interaction')->assertSuccessful();
    File::put(resource_path('css/app.css'), "\xEF\xBB\xBF".'@import "'.shippedPresetImportPath().'";');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('not covered by any generated CSS bundle')
        ->assertSuccessful();
});

it('accepts complete preset imports after allowed CSS prelude rules', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --no-interaction')->assertSuccessful();
    $css = '@charset "UTF-8"; @layer theme; @import "'.shippedPresetImportPath().'";';
    File::put(resource_path('css/app.css'), $css);

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('not covered by any generated CSS bundle')
        ->assertSuccessful();
});

it('accepts an imported application preset as complete coverage', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --no-interaction')->assertSuccessful();
    $this->artisan('hotwire:make-preset brand --from=nova --no-interaction')->assertSuccessful();
    File::put(resource_path('css/app.css'), '@import "./presets/brand.css";');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('not covered by any generated CSS bundle')
        ->assertSuccessful();
});

it('does not treat conditional remote or quoted preset references as complete coverage', function (string $css) {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --no-interaction')->assertSuccessful();
    File::put(resource_path('css/app.css'), str_replace('__PRESET__', shippedPresetImportPath(), $css));

    $exit = Artisan::call('hotwire:check --no-interaction');

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toContain('<x-hw::badge>', 'not covered by any generated CSS bundle');
})->with([
    'conditional import' => '@import url(__PRESET__) print;',
    'remote import' => '@import "https://example.com/resources/css/presets/nova.css";',
    'quoted declaration' => 'body::before { content: \'@import "__PRESET__";\'; }',
    'custom property' => ':root { --example: @import url(__PRESET__); }',
    'at-rule prelude' => '@custom @import url(__PRESET__);',
    'missing local import' => '@import "./missing/resources/css/presets/nova.css";',
    'after style rule' => 'body {} @import "__PRESET__";',
    'after namespace' => '@namespace svg url(http://www.w3.org/2000/svg); @import "__PRESET__";',
]);

it('does not treat an existing impostor path as a shipped preset', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --no-interaction')->assertSuccessful();
    $impostor = resource_path('css/fake/resources/css/presets/nova.css');
    File::ensureDirectoryExists(dirname($impostor));
    File::put($impostor, '');
    File::put(resource_path('css/app.css'), '@import "./fake/resources/css/presets/nova.css";');

    $exit = Artisan::call('hotwire:check --no-interaction');

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toContain('<x-hw::badge>', 'not covered by any generated CSS bundle');
});

it('does not treat a preset-named directory as complete coverage', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --no-interaction')->assertSuccessful();
    File::ensureDirectoryExists(resource_path('css/presets/brand.css'));
    File::put(resource_path('css/app.css'), '@import "./presets/brand.css";');

    $exit = Artisan::call('hotwire:check --no-interaction');

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toContain('<x-hw::badge>', 'not covered by any generated CSS bundle');
});

it('does not treat imports inside preset files as application entrypoints', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --no-interaction')->assertSuccessful();
    $this->artisan('hotwire:make-preset brand --from=nova --no-interaction')->assertSuccessful();
    File::put(resource_path('css/presets/internal.css'), '@import "./brand.css";');

    $exit = Artisan::call('hotwire:check --no-interaction');

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toContain('<x-hw::badge>', 'not covered by any generated CSS bundle');
});

it('ignores commented complete preset imports', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --no-interaction')->assertSuccessful();
    File::put(resource_path('css/app.css'), '/* @import "'.shippedPresetImportPath().'"; */');

    $exit = Artisan::call('hotwire:check --no-interaction');

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toContain('<x-hw::badge>', 'not covered by any generated CSS bundle');
});

it('checks standalone visual controller coverage', function () {
    writeView('page.blade.php', '<button data-controller="tooltip">Help</button>');
    $this->artisan('hotwire:styles --components=badge --no-interaction')->assertSuccessful();

    $exit = Artisan::call('hotwire:check --no-interaction');

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toContain('tooltip', 'not covered by any generated CSS bundle');

    $this->artisan('hotwire:styles --components=badge --include=tooltip --force --no-interaction')->assertSuccessful();
    $exit = Artisan::call('hotwire:check --no-interaction');

    expect($exit)->toBe(0)
        ->and(Artisan::output())->not->toContain('not covered by any generated CSS bundle');
});

it('reports generated CSS without readable metadata once', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    File::ensureDirectoryExists(resource_path('css'));
    File::put(resource_path('css/hotwire.css'), implode("\n", [
        '/* @hotwire-package */',
        '/* Generated by `php artisan hotwire:styles`. Regenerate instead of editing. */',
    ]));

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('generated CSS metadata unavailable')
        ->doesntExpectOutputToContain('not covered by any generated CSS bundle')
        ->assertFailed();
});

it('reports generated CSS whose content no longer matches its metadata', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=badge --no-interaction')->assertSuccessful();
    $css = File::get(resource_path('css/hotwire.css'));
    File::put(resource_path('css/hotwire.css'), strstr($css, '[data-slot="badge"]', true));

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('generated CSS content does not match its plan')
        ->doesntExpectOutputToContain('not covered by any generated CSS bundle')
        ->assertFailed();
});

it('accepts canonical generated CSS checked out with CRLF line endings', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=badge --no-interaction')->assertSuccessful();
    $path = resource_path('css/hotwire.css');
    $css = str_replace(["\r\n", "\r"], "\n", File::get($path));
    File::put($path, str_replace("\n", "\r\n", $css));

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('generated CSS content does not match its plan')
        ->assertSuccessful();
});

it('does not alter selective CSS while fixing and keeps drift failing', function () {
    writeView('page.blade.php', '<x-hw::badge>New</x-hw::badge>');
    $this->artisan('hotwire:styles --components=modal --no-interaction')->assertSuccessful();
    $before = File::get(resource_path('css/hotwire.css'));

    $this->artisan('hotwire:check --fix --no-interaction')->assertFailed();

    expect(File::get(resource_path('css/hotwire.css')))->toBe($before);
});

// --- Detection ---

it('detects component used in a blade file', function () {
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('modal')
        ->assertSuccessful();
});

it('detects component with attributes', function () {
    writeView('page.blade.php', '<x-hw::alert-dialog title="Continue?" description="Sure?"><button>x</button></x-hw::alert-dialog>');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('alert-dialog')
        ->assertSuccessful();
});

it('detects components across multiple files', function () {
    writeView('a.blade.php', '<x-hw::modal />');
    writeView('b.blade.php', '<x-hw::alert-dialog title="x" />');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('<x-hw::modal>')
        ->and($output)->toContain('<x-hw::alert-dialog>');
});

it('deduplicates components used in multiple files', function () {
    // Uses a single-controller component on purpose: the status line is printed
    // once per controller, so the count only isolates the scan-level dedup here.
    writeView('a.blade.php', '<x-hw::dropdown />');
    writeView('b.blade.php', '<x-hw::dropdown />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();
    expect(substr_count($output, 'x-hw::dropdown'))->toBe(1);
});

it('deduplicates a multi-controller component into one line per controller', function () {
    writeView('a.blade.php', '<x-hw::modal />');
    writeView('b.blade.php', '<x-hw::modal />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect(substr_count($output, 'x-hw::modal'))
        ->toBe(count(HotwireRegistry::make()->component('modal')->controllers));
});

it('respects custom prefix', function () {
    config()->set('hotwire.prefix', 'h');
    writeView('page.blade.php', '<x-h::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('modal')
        ->assertSuccessful();
});

it('detects components using hw:: alias', function () {
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('modal')
        ->assertSuccessful();
});

it('detects both configured and hw prefixes in the same codebase', function () {
    config()->set('hotwire.prefix', 'h');
    writeView('a.blade.php', '<x-h::modal />');
    writeView('b.blade.php', '<x-hw::alert-dialog title="x" />');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('<x-h::modal>')
        ->and($output)->toContain('<x-hw::alert-dialog>');
});

it('detects hw:: alias when a custom prefix is set', function () {
    config()->set('hotwire.prefix', 'h');
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('modal')
        ->assertSuccessful();
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

    expect($exit)->toBe(0)
        ->and($output)->toContain('timeago')
        ->and($output)->toContain('used by standalone');
});

it('detects multiple controllers via data-controller attribute', function () {
    writeView('page.blade.php', '<section data-controller="timeago modal">x</section>');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('timeago')
        ->and($output)->toContain('modal');
});

it('detects standalone controller via stimulus_controller()', function () {
    writeView('page.blade.php', '{{ stimulus_controller(\'timeago\', [\'datetime\' => now()]) }}');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('timeago')
        ->and($output)->toContain('used by standalone');
});

it('detects standalone controller via stimulus()->controller()', function () {
    writeView('page.blade.php', '{{ stimulus()->controller(\'tooltip\')->action(\'tooltip\', \'show\', \'mouseenter\') }}');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('tooltip')
        ->and($output)->toContain('used by standalone');
});

it('detects multiple via stimulus()->controllers()', function () {
    writeView('page.blade.php', '{{ stimulus()->controllers(\'modal\', \'alert-dialog\') }}');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('modal')
        ->and($output)->toContain('alert-dialog');
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
    writeView('page.blade.php', '<x-hw::modal>x</x-hw::modal><div data-controller="modal"></div>');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    // Reported once via the component, never again as standalone.
    expect($output)->toContain('modal')
        ->and(substr_count($output, 'used by standalone'))->toBe(0);
});

it('does not double-report a local override used by both a component and standalone', function () {
    File::ensureDirectoryExists($this->targetDir.'/custom/controllers');
    File::put($this->targetDir.'/custom/controllers/modal_controller.js', 'export default class {}');
    writeView('page.blade.php', '<x-hw::modal>x</x-hw::modal><div data-controller="modal"></div>');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($output)->toContain('modal')
        ->and(substr_count($output, 'used by standalone'))->toBe(0);
});

it('detects standalone controller via stimulus_action()', function () {
    writeView('page.blade.php', '{{ stimulus_action(\'carousel\', \'next\') }}');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('carousel')
        ->and($output)->toContain('used by standalone');
});

it('detects standalone controller via stimulus_target()', function () {
    writeView('page.blade.php', '{{ stimulus_target(\'carousel\', \'viewport\') }}');

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(0)
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
    writeView('page.blade.php', '{{-- <x-hw::carousel slide-size="80%"> --}}{{-- </x-hw::carousel> --}}<p>real content</p>');

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
    // Self-contained: pin the package.json so the test isn't sensitive to
    // whichever state the previous test (under random execution order) left.
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('a.blade.php', '<div data-controller="timeago"></div>');
    writeView('b.blade.php', '<div data-controller="timeago"></div>');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    // Match the controller's status line specifically (surrounding spaces),
    // not the dependency hint ("(used by timeago)").
    expect(substr_count($output, ' timeago '))->toBe(1);
});

it('updates outdated standalone controller with --fix', function () {
    $target = $this->targetDir.'/timeago_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, "// @hotwire-package\n// modified");
    writeView('page.blade.php', '<div data-controller="timeago"></div>');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    $source = realpath(__DIR__.'/../../resources/js/controllers/timeago_controller.js');
    expect(File::hash($target))->toBe(File::hash($source));
});

it('reports npm deps for standalone controllers', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<div data-controller="chart"></div>');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('Required npm dependencies')
        ->expectsOutputToContain('echarts')
        ->assertExitCode(1);
});

it('reports auto-loaded from vendor for standalone controller when file is missing', function () {
    writeView('page.blade.php', '<div data-controller="timeago"></div>');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('auto-loaded from vendor')
        ->assertSuccessful();
});

it('reports up to date for standalone controller when file matches', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    publishController('timeago', $this->targetDir);
    writeView('page.blade.php', '<div data-controller="timeago"></div>');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('up to date')
        ->assertSuccessful();
});

// --- Status reporting ---

it('shows auto-loaded from vendor when controller is missing', function () {
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('auto-loaded from vendor')
        ->assertSuccessful();
});

it('shows up to date when controller matches package version', function () {
    publishController('modal', $this->targetDir);
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('up to date')
        ->assertSuccessful();
});

it('shows outdated when controller differs from package version', function () {
    $target = $this->targetDir.'/modal_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, "// @hotwire-package\n// modified");
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('outdated')
        ->assertExitCode(1);
});

it('shows which component requires each controller', function () {
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('x-hw::modal')
        ->assertSuccessful();
});

it('shows dash for component without controller dependency', function () {
    writeView('page.blade.php', '<x-hw::spinner />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('No controllers required')
        ->assertSuccessful();
});

it('groups problem lines under a "Needs attention" heading at the end of the output', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hw::chart />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($output)->toContain('Needs attention');

    $needsAttentionPos = strpos($output, 'Needs attention');
    $depProblemPos = strpos($output, 'echarts');
    $summaryPos = strpos($output, 'npm dependency');

    expect($needsAttentionPos)->toBeLessThan($depProblemPos);
    expect($depProblemPos)->toBeLessThan($summaryPos);
});

it('does not print the "Needs attention" heading when everything is up to date', function () {
    publishController('modal', $this->targetDir);
    writeView('page.blade.php', '<x-hw::modal />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($output)->not->toContain('Needs attention');
});

it('sorts scanned components alphabetically', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => ['@floating-ui/dom' => '^1.8.0']]);
    writeView('a.blade.php', '<x-hw::modal /><x-hw::carousel /><x-hw::dropdown />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    $carouselPos = strpos($output, '<x-hw::carousel>');
    $dropdownPos = strpos($output, '<x-hw::dropdown>');
    $modalPos = strpos($output, '<x-hw::modal>');

    expect($carouselPos)->toBeLessThan($dropdownPos);
    expect($dropdownPos)->toBeLessThan($modalPos);
});

it('sorts the Needs attention block alphabetically', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => ['@floating-ui/dom' => '^1.8.0']]);
    $controllers = ['modal', 'carousel', 'dropdown'];
    foreach ($controllers as $name) {
        $target = "$this->targetDir/{$name}_controller.js";
        File::ensureDirectoryExists(dirname($target));
        File::put($target, "// @hotwire-package\n// modified");
    }
    writeView('a.blade.php', '<x-hw::modal /><x-hw::carousel /><x-hw::dropdown />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    $needsAttentionPos = strpos($output, 'Needs attention');
    $tail = substr($output, $needsAttentionPos);

    $carouselPos = strpos($tail, '  carousel  outdated');
    $dropdownPos = strpos($tail, '  dropdown  outdated');
    $modalPos = strpos($tail, '  modal  outdated');

    expect($carouselPos)->toBeLessThan($dropdownPos);
    expect($dropdownPos)->toBeLessThan($modalPos);
});

it('groups OK output as components -> standalones -> helpers', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => ['@floating-ui/dom' => '^1.8.0']]);
    publishController('dropdown', $this->targetDir);
    publishController('disclosure', $this->targetDir);
    writeView('page.blade.php', '<x-hw::dropdown /><div data-controller="disclosure"></div>');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    $componentPos = strpos($output, '  dropdown  up to date  ');
    $standalonePos = strpos($output, '  disclosure  up to date  ');
    $helperPos = strpos($output, '  _presence.js  up to date  ');

    expect($componentPos)->not->toBeFalse();
    expect($standalonePos)->not->toBeFalse();
    expect($helperPos)->not->toBeFalse();
    expect($componentPos)->toBeLessThan($standalonePos);
    expect($standalonePos)->toBeLessThan($helperPos);
});

it('groups <x-hw::*> "no controllers required" entries after component controllers', function () {
    publishController('modal', $this->targetDir);
    writeView('page.blade.php', '<x-hw::modal /><x-hw::spinner /><x-hw::field />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    $modalPos = strpos($output, '  modal  up to date  ');
    $fieldPos = strpos($output, '<x-hw::field>  No controllers required');
    $spinnerPos = strpos($output, '<x-hw::spinner>  No controllers required');

    expect($modalPos)->toBeLessThan($fieldPos);
    expect($fieldPos)->toBeLessThan($spinnerPos);
});

// --- Exit code ---

it('exits with 0 when all controllers are up to date', function () {
    publishController('modal', $this->targetDir);
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->assertExitCode(0);
});

it('exits with 0 when a controller is auto-loaded from vendor', function () {
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->assertExitCode(0);
});

it('exits with 1 when a controller is outdated', function () {
    $target = $this->targetDir.'/modal_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, "// @hotwire-package\n// modified");
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->assertExitCode(1);
});

// --- --fix flag ---

it('updates outdated controllers with --fix', function () {
    $target = $this->targetDir.'/modal_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, "// @hotwire-package\n// modified");
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    $source = realpath(__DIR__.'/../../resources/js/controllers/modal_controller.js');
    expect(File::hash($target))->toBe(File::hash($source));
});

// --- --path option ---

it('accepts custom path to scan', function () {
    $customDir = resource_path('views/custom');
    File::ensureDirectoryExists($customDir);
    File::put($customDir.'/page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check', ['--path' => [resource_path('views/custom')], '--no-interaction' => true])
        ->expectsOutputToContain('modal')
        ->assertSuccessful();
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
    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('Required npm dependencies')
        ->expectsOutputToContain('echarts')
        ->assertExitCode(1);
});

it('marks dependency as present when listed in dependencies', function () {
    writePackageJson(['name' => 'app', 'dependencies' => ['echarts' => '^6.1.0']]);
    publishController('chart', $this->targetDir);
    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('missing from package.json')
        ->assertSuccessful();
});

it('marks dependency as present when listed in devDependencies', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => ['echarts' => '^6.1.0']]);
    publishController('chart', $this->targetDir);
    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('missing from package.json')
        ->assertSuccessful();
});

it('marks dependency as missing when absent from package.json', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    publishController('chart', $this->targetDir);
    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('echarts')
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
    writeView('page.blade.php', '<x-hw::file name="avatar" />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('_form_errors.js  not published')
        ->assertExitCode(1);
});

it('marks a shared dependency up to date when present', function () {
    publishController('file-preserve', $this->targetDir);
    publishController('reset-files', $this->targetDir);
    File::copy(depSource('_form_errors.js'), $this->targetDir.'/_form_errors.js');
    writeView('page.blade.php', '<x-hw::file name="avatar" />');

    $this->artisan('hotwire:check --no-interaction')
        ->assertSuccessful();
});

it('reports a shared dependency as outdated when it differs', function () {
    publishController('file-preserve', $this->targetDir);
    publishController('reset-files', $this->targetDir);
    File::put($this->targetDir.'/_form_errors.js', "// @hotwire-package\n// modified");
    writeView('page.blade.php', '<x-hw::file name="avatar" />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('_form_errors.js  outdated')
        ->assertExitCode(1);
});

it('publishes a missing shared dependency with --fix', function () {
    publishController('file-preserve', $this->targetDir);
    publishController('reset-files', $this->targetDir);
    writeView('page.blade.php', '<x-hw::file name="avatar" />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    expect(File::hash($this->targetDir.'/_form_errors.js'))
        ->toBe(File::hash(depSource('_form_errors.js')));
});

it('ignores core dependencies', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('@hotwired/stimulus')
        ->doesntExpectOutputToContain('@hotwired/turbo')
        ->doesntExpectOutputToContain('@emaia/stimulus-lazy-loader');
});

it('reports an incompatible v1 lazy loader dependency', function () {
    writeView('page.blade.php', '<div data-controller="modal"></div>');
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^1.1.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), appControllersPath: $this->targetDir),
    );

    $exit = Artisan::call('hotwire:check --no-interaction');

    expect($exit)->toBe(1)
        ->and(Artisan::output())
        ->toContain('@emaia/stimulus-lazy-loader')
        ->toContain('requires ^2.0.0');
});

it('updates an incompatible lazy loader with --fix', function () {
    writeView('page.blade.php', '<div data-controller="modal"></div>');
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^1.1.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), appControllersPath: $this->targetDir),
    );

    $this->artisan('hotwire:check --fix --skip-install --no-interaction')
        ->assertSuccessful();

    expect(readPackageJson()['devDependencies']['@emaia/stimulus-lazy-loader'])->toBe('^2.0.0');
});

it('regenerates a metadata-less loader stub while upgrading v1', function () {
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^1.1.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::put($this->targetDir.'/index.js', <<<'JS'
        // AUTO-GENERATED by hotwire:install — DO NOT EDIT MANUALLY.
        const packageControllers = import.meta.glob(
            "../../../vendor/emaia/laravel-hotwire/resources/js/controllers/**/*_controller.js"
        );
        JS);

    $this->artisan('hotwire:check --fix --skip-install --no-interaction')
        ->assertSuccessful();

    expect(File::get($this->targetDir.'/index.js'))
        ->toContain('// hotwire-loader-plan: {"version":3')
        ->toContain('"includeAllComDepControllers":true');
});

it('updates an incompatible lazy loader even when no view uses Hotwire', function () {
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^1.1.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), appControllersPath: $this->targetDir),
    );

    $this->artisan('hotwire:check --fix --skip-install --no-interaction')
        ->assertSuccessful();

    expect(readPackageJson()['devDependencies']['@emaia/stimulus-lazy-loader'])->toBe('^2.0.0');
});

it('reports and fixes controller policy drift from config', function () {
    writeView('page.blade.php', '<div data-controller="modal"></div>');
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^2.0.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), appControllersPath: $this->targetDir),
    );
    config()->set('hotwire.controllers.preload', ['turbo--progress']);
    config()->set('hotwire.controllers.eager', ['modal']);

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(1)
        ->and($output)
        ->toContain('resources/js/controllers/index.js  outdated')
        ->toContain('controller loading policy differs from config')
        ->toContain('preload: [] -> [turbo--progress]')
        ->toContain('eager: [] -> [modal]')
        ->toContain('1 loader stub needs regeneration')
        ->toContain('hotwire:check --fix will regenerate resources/js/controllers/index.js');

    $this->artisan('hotwire:check --fix --skip-install --no-interaction')
        ->expectsOutputToContain('Regenerated resources/js/controllers/index.js from controller loading config')
        ->expectsOutputToContain('Rebuild your Vite assets so the production manifest includes the regenerated controller loading policy')
        ->assertSuccessful();

    $policy = LoaderStub::policyFromContent(
        File::get($this->targetDir.'/index.js'),
        HotwireRegistry::make(),
    );

    expect($policy->preloadControllers)->toBe(['turbo--progress'])
        ->and($policy->eagerControllers)->toBe(['modal']);
});

it('suggests the detected build command after regenerating the loader stub', function () {
    writeView('page.blade.php', '<div data-controller="modal"></div>');
    writePackageJson([
        'scripts' => ['build' => 'vite build'],
        'devDependencies' => ['@emaia/stimulus-lazy-loader' => '^2.0.0'],
    ]);
    File::put(base_path('bun.lock'), '');
    File::ensureDirectoryExists($this->targetDir);
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), appControllersPath: $this->targetDir),
    );
    config()->set('hotwire.controllers.eager', ['modal']);

    $this->artisan('hotwire:check --fix --skip-install --no-interaction')
        ->expectsOutputToContain('Run `bun run build` after this command completes')
        ->assertSuccessful();
});

it('reports policy drift when an eager package controller gains a local override', function () {
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^2.0.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(
            HotwireRegistry::make(),
            eagerControllers: ['modal'],
            appControllersPath: $this->targetDir,
        ),
    );
    File::put($this->targetDir.'/modal_controller.js', 'export default class {}');
    config()->set('hotwire.controllers.eager', ['modal']);

    $exit = Artisan::call('hotwire:check --no-interaction');

    expect($exit)->toBe(1)
        ->and(Artisan::output())
        ->toContain('controller loading policy differs from config')
        ->toContain('eager paths: [modal=../../../vendor/emaia/laravel-hotwire/resources/js/controllers/modal_controller.js] -> [modal=./modal_controller.js]');
});

it('reports policy drift when an eager local controller falls back to the package', function () {
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^2.0.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::put($this->targetDir.'/modal_controller.js', 'export default class {}');
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(
            HotwireRegistry::make(),
            eagerControllers: ['modal'],
            appControllersPath: $this->targetDir,
        ),
    );
    File::delete($this->targetDir.'/modal_controller.js');
    config()->set('hotwire.controllers.eager', ['modal']);

    $exit = Artisan::call('hotwire:check --no-interaction');

    expect($exit)->toBe(1)
        ->and(Artisan::output())
        ->toContain('controller loading policy differs from config')
        ->toContain('eager paths: [modal=./modal_controller.js] -> [modal=../../../vendor/emaia/laravel-hotwire/resources/js/controllers/modal_controller.js]');
});

it('names the loader stub in the interactive policy drift fix prompt', function () {
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^2.0.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), appControllersPath: $this->targetDir),
    );
    config()->set('hotwire.controllers.eager', ['modal']);

    $this->artisan('hotwire:check')
        ->expectsConfirmation('Apply --fix now? This will regenerate resources/js/controllers/index.js.', 'no')
        ->assertFailed();
});

it('does not apply fixes without a tty unless --fix is explicit', function () {
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^2.0.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    $stubPath = $this->targetDir.'/index.js';
    $original = LoaderStub::generate(HotwireRegistry::make(), appControllersPath: $this->targetDir);
    File::put($stubPath, $original);
    config()->set('hotwire.controllers.eager', ['modal']);
    $environment = app()->environment();

    try {
        app()->instance('env', 'production');
        $exit = Artisan::call('hotwire:check');
    } finally {
        app()->instance('env', $environment);
    }

    expect($exit)->toBe(1)
        ->and(File::get($stubPath))->toBe($original);
});

it('adds dependencies and loader inclusion for configured package preloads', function () {
    writeView('page.blade.php', '<div>Hello</div>');
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^2.0.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), [], appControllersPath: $this->targetDir),
    );
    config()->set('hotwire.controllers.preload', ['chart']);

    $this->artisan('hotwire:check --fix --skip-install --no-interaction')
        ->assertSuccessful();

    $policy = LoaderStub::policyFromContent(
        File::get($this->targetDir.'/index.js'),
        HotwireRegistry::make(),
    );

    expect(readPackageJson()['devDependencies'])->toHaveKey('echarts')
        ->and($policy->includedComDepControllers)->toContain('chart')
        ->and($policy->preloadControllers)->toBe(['chart']);
});

it('does not require builtin dependencies for a configured local override', function () {
    writeView('page.blade.php', '<div>Hello</div>');
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^2.0.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::ensureDirectoryExists($this->targetDir.'/custom/controllers');
    File::put($this->targetDir.'/custom/controllers/chart_controller.js', 'export default class {}');
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), [], appControllersPath: $this->targetDir),
    );
    config()->set('hotwire.controllers.eager', ['chart']);

    $this->artisan('hotwire:check --fix --skip-install --no-interaction')
        ->assertSuccessful();

    expect(readPackageJson()['devDependencies'])->not->toHaveKey('echarts');
});

it('does not require builtin dependencies when a view uses a user-owned local override', function () {
    writeView('page.blade.php', '<div data-controller="chart"></div>');
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^2.0.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::ensureDirectoryExists($this->targetDir.'/custom/controllers');
    File::put($this->targetDir.'/custom/controllers/chart_controller.js', 'export default class {}');
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), [], appControllersPath: $this->targetDir),
    );

    $this->artisan('hotwire:check --fix --skip-install --no-interaction')
        ->assertSuccessful();

    expect(readPackageJson()['devDependencies'])->not->toHaveKey('echarts')
        ->and(LoaderStub::includedComDepControllers(
            File::get($this->targetDir.'/index.js'),
            HotwireRegistry::make(),
        ))->not->toContain('chart');
});

it('recognizes a TypeScript local override when checking view dependencies', function () {
    writeView('page.blade.php', '<div data-controller="chart"></div>');
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^2.0.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::put($this->targetDir.'/chart_controller.ts', 'export default class {}');
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), [], appControllersPath: $this->targetDir),
    );

    $this->artisan('hotwire:check --fix --skip-install --no-interaction')
        ->assertSuccessful();

    expect(readPackageJson()['devDependencies'])->not->toHaveKey('echarts');
});

it('keeps npm checks for a customized published controller', function () {
    writeView('page.blade.php', '<div data-controller="tooltip"></div>');
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^2.0.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    publishController('tooltip', $this->targetDir);
    File::put(
        $this->targetDir.'/tooltip_controller.js',
        str_replace('// @hotwire-package', '// customized published controller', File::get($this->targetDir.'/tooltip_controller.js')),
    );
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), [], appControllersPath: $this->targetDir),
    );

    $this->artisan('hotwire:check --fix --skip-install --no-interaction')
        ->assertSuccessful();

    expect(readPackageJson()['devDependencies'])->toHaveKey('@floating-ui/dom')
        ->and(LoaderStub::includedComDepControllers(
            File::get($this->targetDir.'/index.js'),
            HotwireRegistry::make(),
        ))->not->toContain('tooltip');
});

it('does not accept a v1 constraint containing a minor version two', function () {
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^1.2.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), appControllersPath: $this->targetDir),
    );

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('requires ^2.0.0')
        ->assertFailed();
});

it('preserves non-semver lazy loader protocols during checks', function (string $constraint) {
    writePackageJson(['devDependencies' => [
        '@emaia/stimulus-lazy-loader' => $constraint,
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), appControllersPath: $this->targetDir),
    );

    $this->artisan('hotwire:check --fix --skip-install --no-interaction')
        ->assertSuccessful();

    expect(readPackageJson()['devDependencies']['@emaia/stimulus-lazy-loader'])->toBe($constraint);
})->with(['workspace:*', 'link:../loader', 'file:../loader', 'latest']);

it('adds a missing lazy loader dependency with --fix', function () {
    writePackageJson(['devDependencies' => []]);
    File::ensureDirectoryExists($this->targetDir);
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), appControllersPath: $this->targetDir),
    );

    $this->artisan('hotwire:check --fix --skip-install --no-interaction')
        ->assertSuccessful();

    expect(readPackageJson()['devDependencies']['@emaia/stimulus-lazy-loader'])->toBe('^2.0.0');
});

it('deduplicates dependencies used by multiple controllers', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hw::dropdown /><x-hw::popover />');

    Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect(substr_count($output, '@floating-ui/dom'))->toBe(1);
});

it('exits with 1 when a dependency is missing', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    publishController('chart', $this->targetDir);
    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --no-interaction')
        ->assertExitCode(1);
});

it('adds missing npm dependencies to devDependencies with --fix', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    $json = readPackageJson();
    expect($json['devDependencies'])->toHaveKey('echarts');
});

it('skips package manager install with --skip-install in non-interactive fix mode', function () {
    $installer = fakePackageInstaller('bun');
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --fix --skip-install --no-interaction')
        ->expectsOutputToContain('Run your package manager install command')
        ->assertSuccessful();

    expect($installer->installed)->toBe([]);
});

it('runs package manager install automatically in non-interactive fix mode by default', function () {
    $installer = fakePackageInstaller('bun');
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->expectsOutputToContain('Running bun install')
        ->expectsOutputToContain('bun install completed')
        ->assertSuccessful();

    expect($installer->installed)->toBe(['bun']);
});

it('prompts to run package manager install after interactive fix adds dependencies', function () {
    $installer = fakePackageInstaller('pnpm');
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check')
        ->expectsConfirmation('Apply --fix now? This will add missing npm dependencies.', 'yes')
        ->expectsConfirmation('Run pnpm install now?', 'yes')
        ->expectsOutputToContain('Running pnpm install')
        ->assertSuccessful();

    expect($installer->installed)->toBe(['pnpm']);
});

it('does not run package manager install when no dependencies were added', function () {
    $installer = fakePackageInstaller('bun');
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->doesntExpectOutputToContain('Running bun install')
        ->assertSuccessful();

    expect($installer->installed)->toBe([]);
});

it('fails when package manager install fails', function () {
    $installer = fakePackageInstaller('npm', 1);
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->expectsOutputToContain('npm install failed')
        ->assertFailed();

    expect($installer->installed)->toBe(['npm']);
});

it('does not duplicate a dependency already present when --fix runs', function () {
    writePackageJson(['name' => 'app', 'dependencies' => ['echarts' => '^6.1.0'], 'devDependencies' => []]);
    publishController('chart', $this->targetDir);
    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->assertSuccessful();

    $json = readPackageJson();
    expect($json['dependencies'])->toHaveKey('echarts')
        ->and($json['devDependencies'] ?? [])->not->toHaveKey('echarts');
});

it('warns and skips npm check when package.json does not exist', function () {
    if (File::exists($this->packageJsonPath)) {
        File::delete($this->packageJsonPath);
    }
    publishController('chart', $this->targetDir);
    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('package.json not found')
        ->assertSuccessful();
});

it('reports npm deps even when controllers are not yet published', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => []]);
    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('echarts')
        ->assertExitCode(1);
});

// --- Package marker guard ---

it('refuses to overwrite a user-owned controller when running --fix', function () {
    $target = $this->targetDir.'/modal_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, "// user code, no package marker\nexport default class {}\n");
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->expectsOutputToContain('user-owned')
        ->assertSuccessful();

    expect(File::get($target))->toBe("// user code, no package marker\nexport default class {}\n");
});

it('labels a user-owned divergence as "diverged (user-owned)" and does not act on it with --fix', function () {
    $target = $this->targetDir.'/modal_controller.js';
    File::ensureDirectoryExists(dirname($target));
    File::put($target, "// user code\nexport default class {}\n");
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->expectsOutputToContain('diverged (user-owned)')
        ->doesntExpectOutputToContain('Skipped')
        ->assertSuccessful();

    expect(File::get($target))->toBe("// user code\nexport default class {}\n");
});

it('shows diverged (user-owned) entries even without --fix and keeps the exit code green', function () {
    // Same scenario as the previous test, minus --fix: the gate must NOT
    // short-circuit to "All controllers up to date" just because $issues is
    // empty — the user needs to see the divergence; we just don't fail CI for it.
    writePackageJson(['name' => 'app']);
    publishController('modal', $this->targetDir);
    $target = $this->targetDir.'/modal_controller.js';
    File::put($target, "// user code\nexport default class {}\n");
    writeView('page.blade.php', '<x-hw::modal />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('diverged (user-owned)')
        ->doesntExpectOutputToContain('All controllers up to date')
        ->assertSuccessful();
});

// --- Loader stub drift (auto-generated stub vs views) ---

it('reports a com-dep controller used in views but excluded from the loader stub', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => ['echarts' => '^6.1.0']]);

    // Auto-generated stub that opts into NOTHING (core-only)
    File::ensureDirectoryExists($this->targetDir);
    File::put($this->targetDir.'/index.js',
        LoaderStub::generate(
            HotwireRegistry::make(),
            []
        )
    );

    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --no-interaction')
        ->expectsOutputToContain('excluded from loader stub')
        ->assertExitCode(1);
});

it('prints one regeneration instruction when stub exclusion and policy drift coexist', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^2.0.0',
        'echarts' => '^6.1.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::put(
        $this->targetDir.'/index.js',
        LoaderStub::generate(HotwireRegistry::make(), [], appControllersPath: $this->targetDir),
    );
    writeView('page.blade.php', '<x-hw::chart />');
    config()->set('hotwire.controllers.eager', ['modal']);

    $exit = Artisan::call('hotwire:check --no-interaction');
    $output = Artisan::output();

    expect($exit)->toBe(1)
        ->and($output)->toContain('excluded from loader stub')
        ->toContain('resources/js/controllers/index.js  outdated')
        ->and(substr_count($output, 'will regenerate'))->toBe(1)
        ->and($output)->not->toContain('artisan hotwire:check --fix will regenerate.');
});

it('does not report drift when stub is hand-written (no auto-generated marker)', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => ['echarts' => '^6.1.0']]);

    File::ensureDirectoryExists($this->targetDir);
    File::put($this->targetDir.'/index.js', "// hand-written user file\nimport { Stimulus } from \"../libs/stimulus\";\n");

    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('excluded from loader stub')
        ->assertSuccessful();
});

it('regenerates the loader stub including the missing controller when --fix is used', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => ['echarts' => '^6.1.0']]);

    File::ensureDirectoryExists($this->targetDir);
    File::put($this->targetDir.'/index.js',
        LoaderStub::generate(
            HotwireRegistry::make(),
            []
        )
    );

    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --fix --no-interaction')
        ->expectsOutputToContain('Regenerated resources/js/controllers/index.js')
        ->assertSuccessful();

    $regenerated = File::get($this->targetDir.'/index.js');

    expect($regenerated)
        ->not->toContain('"!**/chart_controller.js"')
        ->toStartWith('// AUTO-GENERATED');
});

it('does not flag drift when the used controller IS included in the stub', function () {
    writePackageJson(['name' => 'app', 'devDependencies' => [
        '@emaia/stimulus-lazy-loader' => '^2.0.0',
        'echarts' => '^6.1.0',
    ]]);
    File::ensureDirectoryExists($this->targetDir);
    File::put($this->targetDir.'/index.js',
        LoaderStub::generate(
            HotwireRegistry::make(),
            ['chart']
        )
    );

    writeView('page.blade.php', '<x-hw::chart />');

    $this->artisan('hotwire:check --no-interaction')
        ->doesntExpectOutputToContain('excluded from loader stub')
        ->assertSuccessful();
});
