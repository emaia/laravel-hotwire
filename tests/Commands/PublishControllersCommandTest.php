<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->targetDir = resource_path('js/controllers');
    $this->fixturesDir = realpath(__DIR__.'/../../resources/js/controllers').'/__fixtures';

    File::deleteDirectory($this->targetDir);
    File::deleteDirectory($this->fixturesDir);

    HotwireRegistry::reset();

    $this->allControllerOptions = collect(HotwireRegistry::make()->publishableControllers())
        ->mapWithKeys(fn ($_, $key) => [$key => $key])
        ->all();
});

afterEach(function () {
    File::deleteDirectory($this->targetDir);
    File::deleteDirectory($this->fixturesDir);
    HotwireRegistry::reset();
});

// --- Helpers ---

function sourceFor(string $key): string
{
    $base = realpath(__DIR__.'/../../resources/js/controllers');
    $path = str_replace('-', '_', $key);

    foreach (['.js', '.ts'] as $ext) {
        $candidate = "{$base}/{$path}_controller{$ext}";
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
    $path = str_replace('-', '_', $key);

    return "{$baseDir}/{$path}_controller.{$ext}";
}

function writeFixture(string $relativePath, string $content): string
{
    $base = realpath(__DIR__.'/../../resources/js/controllers').'/__fixtures';
    $path = "{$base}/{$relativePath}";
    File::ensureDirectoryExists(dirname($path));
    File::put($path, $content);

    return $path;
}

/**
 * Register a fixture controller in the registry for the current test.
 *
 * @param  string  $publishKey  e.g. "__fixtures/chained" or "modal"
 */
function registerFixture(string $publishKey): void
{
    $basePath = realpath(__DIR__.'/../..');
    $catalog = require $basePath.'/src/Registry/catalog.php';

    $extension = file_exists("{$basePath}/resources/js/controllers/{$publishKey}_controller.ts") ? 'ts' : 'js';

    $catalog['controllers'][str_replace('/', '--', $publishKey)] = [
        'source' => "resources/js/controllers/{$publishKey}_controller.{$extension}",
        'docs' => 'README.md',
        'category' => 'fixture',
    ];

    HotwireRegistry::swap(HotwireRegistry::fromCatalog($catalog, $basePath));
}

// --- --list ---

it('lists available controllers', function () {
    $this->artisan('hotwire:controllers --list --no-interaction')
        ->assertSuccessful();
});

// --- Interactive mode ---

it('shows interactive multiselect when no arguments given', function () {
    $this->artisan('hotwire:controllers')
        ->expectsChoice('Which controllers would you like to publish?', ['modal'], $this->allControllerOptions)
        ->assertSuccessful();

    expect(File::exists(targetFor($this->targetDir, 'modal')))->toBeTrue();
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
        ->and(File::exists(targetFor($this->targetDir, 'modal')))->toBeFalse();
});

// --- Top-level and substrate/name notation ---

it('publishes a specific top-level controller by name', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['auto-select']])
        ->assertSuccessful();

    $published = targetFor($this->targetDir, 'auto-select');
    $source = sourceFor('auto-select');

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
    $this->artisan('hotwire:controllers', ['controllers' => ['auto-select', 'turbo/progress']])
        ->assertSuccessful();

    expect(File::exists(targetFor($this->targetDir, 'auto-select')))->toBeTrue()
        ->and(File::exists(targetFor($this->targetDir, 'turbo/progress')))->toBeTrue();
});

it('publishes all controllers with --all', function () {
    $this->artisan('hotwire:controllers', ['--all' => true])
        ->assertSuccessful();

    expect(File::exists(targetFor($this->targetDir, 'modal')))->toBeTrue()
        ->and(File::exists(targetFor($this->targetDir, 'auto-select')))->toBeTrue()
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
    $this->artisan('hotwire:controllers', ['controllers' => ['modal']]);

    $this->artisan('hotwire:controllers', ['controllers' => ['modal']])
        ->assertSuccessful();
});

it('warns when controller exists and differs without --force in non-interactive mode', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['modal']]);

    $published = targetFor($this->targetDir, 'modal');
    File::put($published, '// modified');

    $this->artisan('hotwire:controllers modal --no-interaction')
        ->assertSuccessful();

    expect(File::get($published))->toBe('// modified');
});

it('prompts for confirmation when controller differs in interactive mode', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['modal']]);

    $published = targetFor($this->targetDir, 'modal');
    File::put($published, '// modified');

    $this->artisan('hotwire:controllers', ['controllers' => ['modal']])
        ->expectsConfirmation(
            'Controller "modal" already exists and differs from the package version. Overwrite?',
            'no',
        )
        ->assertSuccessful();

    expect(File::get($published))->toBe('// modified');
});

it('overwrites when user confirms in interactive mode', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['modal']]);

    $published = targetFor($this->targetDir, 'modal');
    File::put($published, '// modified');

    $this->artisan('hotwire:controllers', ['controllers' => ['modal']])
        ->expectsConfirmation(
            'Controller "modal" already exists and differs from the package version. Overwrite?',
            'yes',
        )
        ->assertSuccessful();

    $source = sourceFor('modal');
    expect(File::get($published))->toBe(File::get($source));
});

it('overwrites when controller exists and --force is used', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['modal']]);

    $published = targetFor($this->targetDir, 'modal');
    File::put($published, '// modified');

    $this->artisan('hotwire:controllers', ['controllers' => ['modal'], '--force' => true])
        ->assertSuccessful();

    $source = sourceFor('modal');
    expect(File::get($published))->toBe(File::get($source));
});

// --- Directory structure ---

it('publishes top-level controllers flat', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['modal']]);

    expect(File::isDirectory($this->targetDir))->toBeTrue()
        ->and(File::exists($this->targetDir.'/modal_controller.js'))->toBeTrue()
        ->and(File::isDirectory($this->targetDir.'/modal'))->toBeFalse();
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
    $this->artisan('hotwire:controllers', ['controllers' => ['modal']]);

    $published = targetFor($this->targetDir, 'modal');
    File::delete($published);

    $this->artisan('hotwire:controllers', ['controllers' => ['modal']])
        ->assertSuccessful();

    expect(File::exists($published))->toBeTrue();
});

// --- --list status ---

it('shows up to date and outdated statuses in --list', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['modal']]);

    $outdated = targetFor($this->targetDir, 'auto-select');
    File::ensureDirectoryExists(dirname($outdated));
    File::put($outdated, '// modified');

    $this->artisan('hotwire:controllers --list')
        ->expectsOutputToContain('up to date')
        ->expectsOutputToContain('outdated')
        ->assertSuccessful();
});

// --- Shared dep lifecycle ---

it('leaves shared dep untouched when already up to date on re-run', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['optimistic/form']]);

    $dep = $this->targetDir.'/optimistic/_dispatch.js';
    $before = hash_file('sha256', $dep);

    $this->artisan('hotwire:controllers', ['controllers' => ['optimistic/form']])
        ->assertSuccessful();

    expect(hash_file('sha256', $dep))->toBe($before);
});

it('does not overwrite modified shared dep in non-interactive mode', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['optimistic/form']]);

    $dep = $this->targetDir.'/optimistic/_dispatch.js';
    File::put($dep, '// user-modified');

    $this->artisan('hotwire:controllers optimistic/form --no-interaction')
        ->assertSuccessful();

    expect(File::get($dep))->toBe('// user-modified');
});

// --- Import resolver ---

it('follows transitive relative imports', function () {
    writeFixture('_deep.js', "export const deep = 1;\n");
    writeFixture('_helper.js', "import { deep } from './_deep';\nexport const helper = deep + 1;\n");
    writeFixture(
        'chained_controller.js',
        "import { Controller } from '@hotwired/stimulus';\n".
        "import { helper } from './_helper';\n".
        "import { deep } from './_deep';\n".
        "export default class extends Controller {}\n"
    );
    registerFixture('__fixtures/chained');

    $this->artisan('hotwire:controllers', ['controllers' => ['__fixtures/chained']])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/__fixtures/_helper.js'))->toBeTrue()
        ->and(File::exists($this->targetDir.'/__fixtures/_deep.js'))->toBeTrue();
});

it('ignores imports that do not resolve or point outside the package', function () {
    writeFixture(
        'external_controller.js',
        "import { Controller } from '@hotwired/stimulus';\n".
        "import './_missing';\n".
        "export default class extends Controller {}\n"
    );
    registerFixture('__fixtures/external');

    $this->artisan('hotwire:controllers', ['controllers' => ['__fixtures/external']])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/__fixtures/external_controller.js'))->toBeTrue()
        ->and(File::exists($this->targetDir.'/__fixtures/_missing.js'))->toBeFalse();
});

it('does not publish other controllers as dependencies', function () {
    writeFixture(
        'importer_controller.js',
        "import Sibling from './sibling_controller';\nexport default class {}\n"
    );
    writeFixture('sibling_controller.js', "export default class {}\n");
    registerFixture('__fixtures/importer');

    $this->artisan('hotwire:controllers', ['controllers' => ['__fixtures/importer']])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/__fixtures/importer_controller.js'))->toBeTrue()
        ->and(File::exists($this->targetDir.'/__fixtures/sibling_controller.js'))->toBeFalse();
});

it('resolves directory imports to index files', function () {
    writeFixture('utils/index.js', "export const ok = true;\n");
    writeFixture(
        'indexed_controller.js',
        "import { ok } from './utils';\nexport default class {}\n"
    );
    registerFixture('__fixtures/indexed');

    $this->artisan('hotwire:controllers', ['controllers' => ['__fixtures/indexed']])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/__fixtures/utils/index.js'))->toBeTrue();
});

it('publishes CSS imported by a controller', function () {
    writeFixture('styled.css', ".styled { color: red; }\n");
    writeFixture(
        'styled_controller.js',
        "import './styled.css';\nexport default class {}\n"
    );
    registerFixture('__fixtures/styled');

    $this->artisan('hotwire:controllers', ['controllers' => ['__fixtures/styled']])
        ->assertSuccessful();

    expect(File::exists($this->targetDir.'/__fixtures/styled.css'))->toBeTrue();
});

// --- --outdated ---

it('does nothing when no controllers are installed when using --outdated', function () {
    $this->artisan('hotwire:controllers --outdated --no-interaction')
        ->assertSuccessful();

    expect(File::isDirectory($this->targetDir))->toBeFalse();
});

it('skips controllers that are already up to date when using --outdated', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['modal']]);

    $published = targetFor($this->targetDir, 'modal');
    $before = File::get($published);

    $this->artisan('hotwire:controllers --outdated --force --no-interaction')
        ->assertSuccessful();

    expect(File::get($published))->toBe($before);
});

it('updates only outdated published controllers, ignoring not-published ones', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['modal', 'auto-select']]);

    $modal = targetFor($this->targetDir, 'modal');
    $autoSelect = targetFor($this->targetDir, 'auto-select');

    File::put($modal, '// outdated');

    $this->artisan('hotwire:controllers --outdated --force --no-interaction')
        ->assertSuccessful();

    $source = sourceFor('modal');
    expect(File::get($modal))->toBe(File::get($source))
        ->and(File::get($autoSelect))->toBe(File::get(sourceFor('auto-select')));
});

it('does not publish controllers that are not yet installed when using --outdated', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['modal']]);

    File::put(targetFor($this->targetDir, 'modal'), '// outdated');

    $this->artisan('hotwire:controllers --outdated --force --no-interaction')
        ->assertSuccessful();

    expect(File::exists(targetFor($this->targetDir, 'auto-select')))->toBeFalse();
});

it('prompts per controller when --outdated is used interactively without --force', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['modal']]);

    File::put(targetFor($this->targetDir, 'modal'), '// outdated');

    $this->artisan('hotwire:controllers --outdated')
        ->expectsConfirmation(
            'Controller "modal" already exists and differs from the package version. Overwrite?',
            'yes',
        )
        ->assertSuccessful();

    expect(File::get(targetFor($this->targetDir, 'modal')))->toBe(File::get(sourceFor('modal')));
});

it('warns and skips outdated controllers in non-interactive mode without --force', function () {
    $this->artisan('hotwire:controllers', ['controllers' => ['modal']]);

    File::put(targetFor($this->targetDir, 'modal'), '// outdated');

    $this->artisan('hotwire:controllers --outdated --no-interaction')
        ->assertSuccessful();

    expect(File::get(targetFor($this->targetDir, 'modal')))->toBe('// outdated');
});
