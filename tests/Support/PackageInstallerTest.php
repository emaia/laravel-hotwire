<?php

use Emaia\LaravelHotwire\Support\PackageInstaller;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->installer = new PackageInstaller;
    $this->files = new Filesystem;
    $this->packageJsonPath = base_path('package.json');
    $this->originalPackageJson = File::exists($this->packageJsonPath)
        ? File::get($this->packageJsonPath)
        : null;
});

afterEach(function () {
    if ($this->originalPackageJson !== null) {
        File::put($this->packageJsonPath, $this->originalPackageJson);
    } elseif (File::exists($this->packageJsonPath)) {
        File::delete($this->packageJsonPath);
    }
});

function writeInstallerPackageJson(array $json): void
{
    File::put(
        base_path('package.json'),
        json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
    );
}

// --- addDevDependencies ---

it('adds a new package to devDependencies', function () {
    writeInstallerPackageJson(['name' => 'app', 'devDependencies' => new stdClass]);

    $changed = $this->installer->addDevDependencies($this->files, ['maska' => '^3.0.0']);

    $json = json_decode(File::get($this->packageJsonPath), true);

    expect($changed)->toBe(['maska' => '^3.0.0'])
        ->and($json['devDependencies']['maska'])->toBe('^3.0.0');
});

it('preserves existing devDependencies when adding', function () {
    writeInstallerPackageJson(['name' => 'app', 'devDependencies' => ['vite' => '^5.0.0']]);

    $this->installer->addDevDependencies($this->files, ['maska' => '^3.0.0']);

    $json = json_decode(File::get($this->packageJsonPath), true);

    expect($json['devDependencies']['vite'])->toBe('^5.0.0')
        ->and($json['devDependencies']['maska'])->toBe('^3.0.0');
});

it('does not rewrite the file when nothing changes', function () {
    writeInstallerPackageJson(['name' => 'app', 'devDependencies' => ['maska' => '^3.0.0']]);
    $before = File::get($this->packageJsonPath);

    $changed = $this->installer->addDevDependencies($this->files, ['maska' => '^3.0.0']);

    expect($changed)->toBe([])
        ->and(File::get($this->packageJsonPath))->toBe($before);
});

it('updates a differing version by default', function () {
    writeInstallerPackageJson(['name' => 'app', 'devDependencies' => ['maska' => '^2.0.0']]);

    $changed = $this->installer->addDevDependencies($this->files, ['maska' => '^3.0.0']);

    $json = json_decode(File::get($this->packageJsonPath), true);

    expect($changed)->toBe(['maska' => '^3.0.0'])
        ->and($json['devDependencies']['maska'])->toBe('^3.0.0');
});

it('skips an already-present package when updateExisting is false', function () {
    writeInstallerPackageJson(['name' => 'app', 'devDependencies' => ['maska' => '^2.0.0']]);
    $before = File::get($this->packageJsonPath);

    $changed = $this->installer->addDevDependencies($this->files, ['maska' => '^3.0.0'], updateExisting: false);

    expect($changed)->toBe([])
        ->and(File::get($this->packageJsonPath))->toBe($before);
});

it('preserves all unrelated keys in package.json', function () {
    writeInstallerPackageJson([
        'name' => 'my-app',
        'private' => true,
        'type' => 'module',
        'scripts' => ['dev' => 'vite', 'build' => 'vite build'],
        'dependencies' => ['axios' => '^1.0.0'],
        'devDependencies' => ['vite' => '^5.0.0'],
    ]);

    $this->installer->addDevDependencies($this->files, ['maska' => '^3.0.0']);

    $json = json_decode(File::get($this->packageJsonPath), true);

    expect($json['name'])->toBe('my-app')
        ->and($json['private'])->toBeTrue()
        ->and($json['type'])->toBe('module')
        ->and($json['scripts'])->toBe(['dev' => 'vite', 'build' => 'vite build'])
        ->and($json['dependencies'])->toBe(['axios' => '^1.0.0'])
        ->and($json['devDependencies']['vite'])->toBe('^5.0.0')
        ->and($json['devDependencies']['maska'])->toBe('^3.0.0');
});

it('returns empty and writes nothing when package.json is invalid JSON', function () {
    File::put($this->packageJsonPath, 'not json');

    $changed = $this->installer->addDevDependencies($this->files, ['maska' => '^3.0.0']);

    expect($changed)->toBe([]);
});

it('returns empty and writes nothing when package.json is null', function () {
    File::put($this->packageJsonPath, 'null');

    $changed = $this->installer->addDevDependencies($this->files, ['maska' => '^3.0.0']);

    expect($changed)->toBe([]);
});

// --- addViteAlias ---

beforeEach(function () {
    $this->viteCandidates = [
        base_path('vite.config.ts'),
        base_path('vite.config.mjs'),
        base_path('vite.config.js'),
    ];
    foreach ($this->viteCandidates as $candidate) {
        $this->{'vite_'.basename($candidate)} = File::exists($candidate) ? File::get($candidate) : null;
    }
});

afterEach(function () {
    foreach ($this->viteCandidates as $candidate) {
        $key = 'vite_'.basename($candidate);
        $original = $this->{$key} ?? null;

        if ($original !== null) {
            File::put($candidate, $original);
        } elseif (File::exists($candidate)) {
            File::delete($candidate);
        }
    }
});

function laravelStockViteConfig(): string
{
    return <<<'JS'
        import { defineConfig } from 'vite';
        import laravel from 'laravel-vite-plugin';
        import tailwindcss from '@tailwindcss/vite';

        export default defineConfig({
            plugins: [
                laravel({
                    input: ['resources/css/app.css', 'resources/js/app.js'],
                    refresh: true,
                }),
                tailwindcss(),
            ],
        });
        JS;
}

it('injects the @hotwire alias into a stock Laravel vite.config.js', function () {
    File::put(base_path('vite.config.js'), laravelStockViteConfig());

    $result = $this->installer->addViteAlias($this->files, '@hotwire', 'vendor/emaia/laravel-hotwire/resources/js');

    expect($result)->toBe(PackageInstaller::VITE_ALIAS_ADDED);

    $written = File::get(base_path('vite.config.js'));

    expect($written)
        ->toContain("import { fileURLToPath } from 'node:url';")
        ->toContain("'@hotwire': fileURLToPath(new URL('vendor/emaia/laravel-hotwire/resources/js', import.meta.url)),")
        ->toContain('resolve: {')
        ->toContain('alias: {');
});

it('returns already_present without rewriting when the alias key is already there', function () {
    $config = laravelStockViteConfig();
    $configWithAlias = str_replace(
        "export default defineConfig({\n",
        "export default defineConfig({\n    resolve: { alias: { '@hotwire': './custom' } },\n",
        $config,
    );

    File::put(base_path('vite.config.js'), $configWithAlias);
    $before = File::get(base_path('vite.config.js'));

    $result = $this->installer->addViteAlias($this->files, '@hotwire', 'vendor/emaia/laravel-hotwire/resources/js');

    expect($result)->toBe(PackageInstaller::VITE_ALIAS_ALREADY_PRESENT)
        ->and(File::get(base_path('vite.config.js')))->toBe($before);
});

it('returns no_config when no vite config file exists', function () {
    foreach ($this->viteCandidates as $candidate) {
        if (File::exists($candidate)) {
            File::delete($candidate);
        }
    }

    $result = $this->installer->addViteAlias($this->files, '@hotwire', 'vendor/emaia/laravel-hotwire/resources/js');

    expect($result)->toBe(PackageInstaller::VITE_ALIAS_NO_CONFIG);
});

it('returns pattern_mismatch and writes nothing when defineConfig is absent', function () {
    $custom = <<<'JS'
        // Custom config without defineConfig wrapper
        export default {
            plugins: [],
        };
        JS;

    File::put(base_path('vite.config.js'), $custom);

    $result = $this->installer->addViteAlias($this->files, '@hotwire', 'vendor/emaia/laravel-hotwire/resources/js');

    expect($result)->toBe(PackageInstaller::VITE_ALIAS_PATTERN_MISMATCH)
        ->and(File::get(base_path('vite.config.js')))->toBe($custom);
});

it('prefers vite.config.ts over .mjs and .js', function () {
    File::put(base_path('vite.config.ts'), laravelStockViteConfig());
    File::put(base_path('vite.config.js'), laravelStockViteConfig());

    $result = $this->installer->addViteAlias($this->files, '@hotwire', 'vendor/emaia/laravel-hotwire/resources/js');

    expect($result)->toBe(PackageInstaller::VITE_ALIAS_ADDED)
        ->and(File::get(base_path('vite.config.ts')))->toContain('@hotwire')
        ->and(File::get(base_path('vite.config.js')))->not->toContain('@hotwire');
});

it('does not duplicate the fileURLToPath import when already present', function () {
    $config = "import { fileURLToPath } from 'node:url';\n".laravelStockViteConfig();

    File::put(base_path('vite.config.js'), $config);

    $this->installer->addViteAlias($this->files, '@hotwire', 'vendor/emaia/laravel-hotwire/resources/js');

    $written = File::get(base_path('vite.config.js'));
    $importCount = substr_count($written, "import { fileURLToPath } from 'node:url';");

    expect($importCount)->toBe(1);
});
