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

it('returns empty and writes nothing when package.json is missing', function () {
    if (File::exists($this->packageJsonPath)) {
        File::delete($this->packageJsonPath);
    }

    $changed = $this->installer->addDevDependencies($this->files, ['maska' => '^3.0.0']);

    expect($changed)->toBe([])
        ->and(File::exists($this->packageJsonPath))->toBeFalse();
});
