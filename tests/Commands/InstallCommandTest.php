<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->stubBase = realpath(__DIR__.'/../../stubs/resources');

    // Clean target directories
    File::deleteDirectory(resource_path('js'));
    File::deleteDirectory(resource_path('css'));

    // Save original package.json if it exists
    $this->packageJsonPath = base_path('package.json');
    $this->originalPackageJson = File::exists($this->packageJsonPath)
        ? File::get($this->packageJsonPath)
        : null;
});

afterEach(function () {
    File::deleteDirectory(resource_path('js'));
    File::deleteDirectory(resource_path('css'));

    // Restore original package.json
    if ($this->originalPackageJson !== null) {
        File::put($this->packageJsonPath, $this->originalPackageJson);
    } elseif (File::exists($this->packageJsonPath)) {
        File::delete($this->packageJsonPath);
    }

    // Clean up lock files
    foreach (['bun.lock', 'pnpm-lock.yaml', 'yarn.lock', 'package-lock.json'] as $lock) {
        $path = base_path($lock);
        if (File::exists($path)) {
            File::delete($path);
        }
    }
});

// --- Phase 1: Basic copy ---

it('copies all stub files to resources directory', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')
        ->assertSuccessful();

    $expectedFiles = [
        'css/app.css',
        'js/app.js',
        'js/controllers/index.js',
        'js/libs/index.js',
        'js/libs/stimulus.js',
        'js/libs/turbo.js',
    ];

    foreach ($expectedFiles as $file) {
        $target = resource_path($file);
        expect(File::exists($target))->toBeTrue("Expected {$file} to exist");

        $source = $this->stubBase.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $file);
        expect(File::get($target))->toBe(File::get($source));
    }
});

it('creates necessary subdirectories', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')
        ->assertSuccessful();

    expect(File::isDirectory(resource_path('js/libs')))->toBeTrue()
        ->and(File::isDirectory(resource_path('js/controllers')))->toBeTrue()
        ->and(File::isDirectory(resource_path('css')))->toBeTrue();
});

// --- Phase 2: Conflict detection ---

it('skips files that are identical to stubs', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')->assertSuccessful();
    $this->artisan('hotwire:install --no-interaction')->assertSuccessful();

    // Files should still match stubs
    $source = $this->stubBase.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'app.js';
    expect(File::get(resource_path('js/app.js')))->toBe(File::get($source));
});

it('prompts when file differs in interactive mode and user declines', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')->assertSuccessful();

    File::put(resource_path('js/app.js'), '// modified');

    $this->artisan('hotwire:install')
        ->expectsConfirmation(
            'File "js/app.js" already exists and differs. Overwrite?',
            'no',
        )
        ->assertSuccessful();

    expect(File::get(resource_path('js/app.js')))->toBe('// modified');
});

it('overwrites when user confirms in interactive mode', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')->assertSuccessful();

    File::put(resource_path('js/app.js'), '// modified');

    $this->artisan('hotwire:install')
        ->expectsConfirmation(
            'File "js/app.js" already exists and differs. Overwrite?',
            'yes',
        )
        ->assertSuccessful();

    $source = $this->stubBase.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'app.js';
    expect(File::get(resource_path('js/app.js')))->toBe(File::get($source));
});

it('skips differing files in non-interactive mode without --force', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')->assertSuccessful();

    File::put(resource_path('js/app.js'), '// modified');

    $this->artisan('hotwire:install --no-interaction')
        ->assertSuccessful();

    expect(File::get(resource_path('js/app.js')))->toBe('// modified');
});

it('overwrites all files with --force', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')->assertSuccessful();

    File::put(resource_path('js/app.js'), '// modified');

    $this->artisan('hotwire:install --force --no-interaction')
        ->assertSuccessful();

    $source = $this->stubBase.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'app.js';
    expect(File::get(resource_path('js/app.js')))->toBe(File::get($source));
});

// --- Phase 3: npm dependencies ---

it('adds core npm dependencies to package.json in non-interactive mode', function () {
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => new stdClass,
    ], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --core-only --no-interaction')
        ->assertSuccessful();

    $json = json_decode(File::get($this->packageJsonPath), true);

    expect($json['devDependencies'])
        ->toHaveKey('@emaia/stimulus-dynamic-loader')
        ->toHaveKey('@hotwired/stimulus')
        ->toHaveKey('@hotwired/turbo')
        ->not->toHaveKey('maska')
        ->not->toHaveKey('tippy.js')
        ->not->toHaveKey('@emaia/sonner');
});

it('reads dependency versions from the package own package.json', function () {
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => new stdClass,
    ], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')
        ->assertSuccessful();

    $packageJson = json_decode(file_get_contents(__DIR__.'/../../package.json'), true);
    $appJson = json_decode(File::get($this->packageJsonPath), true);

    foreach (['@hotwired/stimulus', '@hotwired/turbo', '@emaia/stimulus-dynamic-loader'] as $dep) {
        expect($appJson['devDependencies'][$dep])->toBe($packageJson['dependencies'][$dep]);
    }
});

it('installs core + all catalog dependencies by default', function () {
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => new stdClass,
    ], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')
        ->assertSuccessful();

    $json = json_decode(File::get($this->packageJsonPath), true);

    expect($json['devDependencies'])
        ->toHaveKey('@hotwired/stimulus')
        ->toHaveKey('@hotwired/turbo')
        ->toHaveKey('@emaia/stimulus-dynamic-loader')
        ->toHaveKey('echarts')
        ->toHaveKey('leaflet')
        ->toHaveKey('embla-carousel')
        ->toHaveKey('@emaia/sonner');
});

it('preserves existing dependencies in package.json', function () {
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => [
            'vite' => '^5.0.0',
        ],
    ], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')
        ->assertSuccessful();

    $json = json_decode(File::get($this->packageJsonPath), true);

    expect($json['devDependencies']['vite'])->toBe('^5.0.0')
        ->and($json['devDependencies'])->toHaveKey('@hotwired/stimulus');
});

it('does not modify package.json when core deps already present (--core-only)', function () {
    $packageJson = json_decode(file_get_contents(__DIR__.'/../../package.json'), true);
    $coreDeps = [];
    foreach (['@emaia/stimulus-dynamic-loader', '@hotwired/stimulus', '@hotwired/turbo'] as $dep) {
        $coreDeps[$dep] = $packageJson['dependencies'][$dep];
    }

    $content = json_encode([
        'name' => 'test',
        'devDependencies' => $coreDeps,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

    File::put($this->packageJsonPath, $content);

    $this->artisan('hotwire:install --core-only --no-interaction')
        ->assertSuccessful();

    expect(File::get($this->packageJsonPath))->toBe($content);
});

it('warns when package.json does not exist', function () {
    if (File::exists($this->packageJsonPath)) {
        File::delete($this->packageJsonPath);
    }

    $this->artisan('hotwire:install --no-interaction')
        ->assertSuccessful();

    // Should not crash — files should still be copied
    expect(File::exists(resource_path('js/app.js')))->toBeTrue();
});

// --- Phase 4: --only filter ---

it('copies only js files with --only=js', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --only=js --no-interaction')
        ->assertSuccessful();

    expect(File::exists(resource_path('js/app.js')))->toBeTrue()
        ->and(File::exists(resource_path('css/app.css')))->toBeFalse();
});

it('copies only css files with --only=css', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --only=css --no-interaction')
        ->assertSuccessful();

    expect(File::exists(resource_path('css/app.css')))->toBeTrue()
        ->and(File::exists(resource_path('js/app.js')))->toBeFalse();
});

it('rejects invalid --only value', function () {
    $this->artisan('hotwire:install --only=html --no-interaction')
        ->assertFailed();
});

it('skips npm dependencies when --only=css', function () {
    $content = json_encode(['name' => 'test', 'devDependencies' => new stdClass], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    File::put($this->packageJsonPath, $content);

    $this->artisan('hotwire:install --only=css --no-interaction')
        ->assertSuccessful();

    expect(File::get($this->packageJsonPath))->toBe($content);
});

// --- Phase 5: Post-install instructions ---

it('shows post-install instructions with detected package manager', function () {
    fakePackageInstaller('bun');
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')
        ->expectsOutputToContain('bun install')
        ->assertSuccessful();
});

it('defaults to npm when no lock file found', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')
        ->expectsOutputToContain('npm install')
        ->assertSuccessful();
});

it('shows summary of actions taken', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')
        ->expectsOutputToContain('Hotwire installed successfully')
        ->assertSuccessful();
});

it('points users to discovery and customisation commands', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')
        ->expectsOutputToContain('hotwire:components')
        ->expectsOutputToContain('hotwire:controllers --list')
        ->expectsOutputToContain('hotwire:check')
        ->expectsOutputToContain('hotwire:controllers <name>')
        ->expectsOutputToContain('auto-load')
        ->assertSuccessful();
});

// --- Phase 6: --with-deps / --core-only ---

it('adds only specified controller dependencies with --with-deps=chart', function () {
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => new stdClass,
    ], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --with-deps=chart --no-interaction')
        ->assertSuccessful();

    $json = json_decode(File::get($this->packageJsonPath), true);

    expect($json['devDependencies'])
        ->toHaveKey('@hotwired/stimulus')
        ->toHaveKey('echarts')
        ->not->toHaveKey('embla-carousel')
        ->not->toHaveKey('leaflet')
        ->not->toHaveKey('@emaia/sonner');
});

it('accepts comma-separated controllers in --with-deps', function () {
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => new stdClass,
    ], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --with-deps=chart,carousel,map --no-interaction')
        ->assertSuccessful();

    $json = json_decode(File::get($this->packageJsonPath), true);

    expect($json['devDependencies'])
        ->toHaveKey('echarts')
        ->toHaveKey('embla-carousel')
        ->toHaveKey('leaflet')
        ->not->toHaveKey('@emaia/sonner');
});

it('accepts repeated --with-deps flags', function () {
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => new stdClass,
    ], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --with-deps=chart --with-deps=carousel --no-interaction')
        ->assertSuccessful();

    $json = json_decode(File::get($this->packageJsonPath), true);

    expect($json['devDependencies'])
        ->toHaveKey('echarts')
        ->toHaveKey('embla-carousel')
        ->not->toHaveKey('leaflet');
});

it('installs only core deps with --core-only', function () {
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => new stdClass,
    ], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --core-only --no-interaction')
        ->assertSuccessful();

    $json = json_decode(File::get($this->packageJsonPath), true);

    expect($json['devDependencies'])
        ->toHaveKey('@hotwired/stimulus')
        ->toHaveKey('@hotwired/turbo')
        ->toHaveKey('@emaia/stimulus-dynamic-loader')
        ->not->toHaveKey('echarts')
        ->not->toHaveKey('leaflet')
        ->not->toHaveKey('@emaia/sonner');
});

it('rejects --core-only combined with --with-deps', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --core-only --with-deps=chart --no-interaction')
        ->assertFailed();
});

it('fails with unknown controller name in --with-deps', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --with-deps=nonexistent --no-interaction')
        ->assertFailed();
});

// --- Phase 6b: Loader stub generation per mode ---

it('writes a loader stub with no exclusions in default mode', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')
        ->assertSuccessful();

    $stub = File::get(resource_path('js/controllers/index.js'));

    expect($stub)
        ->toStartWith('// AUTO-GENERATED')
        ->not->toContain('"!**/')
        ->toContain('"../../../vendor/emaia/laravel-hotwire/resources/js/controllers/**/*_controller.js"');
});

it('writes a loader stub with com-dep exclusions in --core-only mode', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --core-only --no-interaction')
        ->assertSuccessful();

    $stub = File::get(resource_path('js/controllers/index.js'));

    expect($stub)
        ->toStartWith('// AUTO-GENERATED')
        ->toContain('"!**/carousel_controller.js"')
        ->toContain('"!**/chart_controller.js"')
        ->toContain('"!**/map_controller.js"')
        ->not->toContain('"!**/modal_controller.js"')
        ->not->toContain('"!**/dropdown_controller.js"');
});

it('writes a loader stub with only non-opted-in com-deps excluded with --with-deps', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --with-deps=carousel,chart --no-interaction')
        ->assertSuccessful();

    $stub = File::get(resource_path('js/controllers/index.js'));

    expect($stub)
        ->not->toContain('"!**/carousel_controller.js"')
        ->not->toContain('"!**/chart_controller.js"')
        ->toContain('"!**/map_controller.js"')
        ->toContain('"!**/rich_text_controller.js"');
});

it('silently regenerates an existing auto-generated stub even without --force', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    // First install: default mode, no exclusions
    $this->artisan('hotwire:install --no-interaction')->assertSuccessful();

    // Second install: --core-only — must rewrite the stub silently
    $this->artisan('hotwire:install --core-only --no-interaction')
        ->doesntExpectOutputToContain('already exists')
        ->assertSuccessful();

    $stub = File::get(resource_path('js/controllers/index.js'));
    expect($stub)->toContain('"!**/chart_controller.js"');
});

it('preserves a hand-written stub (no marker) unless --force is passed', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));
    $targetPath = resource_path('js/controllers/index.js');
    File::ensureDirectoryExists(dirname($targetPath));
    $userContent = "// custom user file, no marker\nconsole.log('hello');\n";
    File::put($targetPath, $userContent);

    $this->artisan('hotwire:install --no-interaction')
        ->expectsOutputToContain('already exists')
        ->assertSuccessful();

    expect(File::get($targetPath))->toBe($userContent);
});

// --- Phase 6c: Auto-run hotwire:check after install ---

it('skips post-install check in default mode (no exclusions, no drift possible)', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')
        ->doesntExpectOutputToContain('Verifying view usage')
        ->assertSuccessful();
});

it('runs post-install check when --core-only is used', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --core-only --no-interaction')
        ->expectsOutputToContain('Verifying view usage')
        ->assertSuccessful();
});

it('runs post-install check when --with-deps is used', function () {
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --with-deps=modal --no-interaction')
        ->expectsOutputToContain('Verifying view usage')
        ->assertSuccessful();
});

// --- Phase 7: package manager install (default) and --skip-install opt-out ---

it('runs package manager install automatically in non-interactive mode by default', function () {
    $installer = fakePackageInstaller('bun');
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => new stdClass,
    ], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')
        ->assertSuccessful()
        ->expectsOutputToContain('Running bun install')
        ->expectsOutputToContain('bun install completed');

    expect($installer->installed)->toBe(['bun']);
});

it('omits the "Run X install" next-steps line when install ran', function () {
    fakePackageInstaller('bun');
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => new stdClass,
    ], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction')
        ->assertSuccessful()
        ->doesntExpectOutputToContain('Run `bun install`')
        ->expectsOutputToContain('Run `bun run dev`');
});

it('skips package manager install with --skip-install', function () {
    $installer = fakePackageInstaller('bun');
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => new stdClass,
    ], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install --no-interaction --skip-install')
        ->assertSuccessful()
        ->doesntExpectOutputToContain('Running bun install')
        ->expectsOutputToContain('Run `bun install`');

    expect($installer->installed)->toBe([]);
});

it('prompts to run install in interactive mode (default yes)', function () {
    $installer = fakePackageInstaller('bun');
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => new stdClass,
    ], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:install')
        ->expectsConfirmation('Run bun install now?', 'yes')
        ->expectsOutputToContain('bun install completed')
        ->assertSuccessful();

    expect($installer->installed)->toBe(['bun']);
});
