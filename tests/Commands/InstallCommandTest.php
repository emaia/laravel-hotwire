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

    // Build optional deps options for interactive tests
    $packageJson = json_decode(file_get_contents(__DIR__.'/../../package.json'), true);
    $allDeps = $packageJson['dependencies'] ?? [];
    $coreDeps = ['@emaia/stimulus-dynamic-loader', '@hotwired/stimulus', '@hotwired/turbo'];

    $this->optionalDepsOptions = [];
    foreach ($allDeps as $pkg => $version) {
        if (! in_array($pkg, $coreDeps)) {
            $this->optionalDepsOptions[$pkg] = "{$pkg} {$version}";
        }
    }
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
        ->expectsChoice(
            'Which optional npm dependencies would you like to install?',
            [],
            $this->optionalDepsOptions,
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
        ->expectsChoice(
            'Which optional npm dependencies would you like to install?',
            [],
            $this->optionalDepsOptions,
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

    $this->artisan('hotwire:install --no-interaction')
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

it('shows multiselect for optional dependencies in interactive mode', function () {
    File::put($this->packageJsonPath, json_encode([
        'name' => 'test',
        'devDependencies' => new stdClass,
    ], JSON_PRETTY_PRINT));

    $selectedKey = array_key_first($this->optionalDepsOptions);

    $this->artisan('hotwire:install')
        ->expectsChoice(
            'Which optional npm dependencies would you like to install?',
            [$selectedKey],
            $this->optionalDepsOptions,
        )
        ->assertSuccessful();

    $json = json_decode(File::get($this->packageJsonPath), true);

    // Core deps always installed
    expect($json['devDependencies'])->toHaveKey('@hotwired/stimulus');

    // Selected optional dep installed
    expect($json['devDependencies'])->toHaveKey($selectedKey);
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

it('does not modify package.json when dependencies already present', function () {
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

    $this->artisan('hotwire:install --no-interaction')
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
    File::put($this->packageJsonPath, json_encode(['name' => 'test'], JSON_PRETTY_PRINT));
    File::put(base_path('bun.lock'), '');

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
