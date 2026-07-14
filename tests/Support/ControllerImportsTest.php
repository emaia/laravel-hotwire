<?php

use Emaia\LaravelHotwire\Support\ControllerImports;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->imports = new ControllerImports(new Filesystem);
    $this->dir = sys_get_temp_dir().'/hwc-imports-'.uniqid();
    mkdir($this->dir);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->dir);
});

function writeFile(string $dir, string $name, string $contents): string
{
    $path = $dir.'/'.$name;
    file_put_contents($path, $contents);

    return (string) realpath($path);
}

// --- sharedDependencies ---

it('resolves a relative import to a shared file', function () {
    writeFile($this->dir, '_form_errors.js', 'export function f() {}');
    $controller = writeFile($this->dir, 'a_controller.js', 'import { f } from "./_form_errors";');

    expect($this->imports->sharedDependencies($controller, $this->dir))
        ->toBe([realpath($this->dir.'/_form_errors.js')]);
});

it('ignores imports of other controllers', function () {
    writeFile($this->dir, 'b_controller.js', 'export default class {}');
    $controller = writeFile($this->dir, 'a_controller.js', 'import B from "./b_controller";');

    expect($this->imports->sharedDependencies($controller, $this->dir))->toBe([]);
});

it('ignores bare package imports', function () {
    $controller = writeFile($this->dir, 'a_controller.js', 'import { Controller } from "@hotwired/stimulus";');

    expect($this->imports->sharedDependencies($controller, $this->dir))->toBe([]);
});

it('follows transitive shared dependencies', function () {
    writeFile($this->dir, '_b.js', 'export const b = 1;');
    writeFile($this->dir, '_a.js', 'import { b } from "./_b";');
    $controller = writeFile($this->dir, 'c_controller.js', 'import { a } from "./_a";');

    expect($this->imports->sharedDependencies($controller, $this->dir))
        ->toContain(realpath($this->dir.'/_a.js'))
        ->toContain(realpath($this->dir.'/_b.js'));
});

it('deduplicates a dependency reached more than once', function () {
    writeFile($this->dir, '_shared.js', 'export const x = 1;');
    $controller = writeFile($this->dir, 'a_controller.js', "import { x } from \"./_shared\";\nimport { x as y } from \"./_shared\";");

    expect($this->imports->sharedDependencies($controller, $this->dir))
        ->toBe([realpath($this->dir.'/_shared.js')]);
});

it('resolves the dropdown controller shared positioning helpers', function () {
    $base = realpath(__DIR__.'/../../resources/js/controllers');
    $source = realpath($base.'/dropdown_controller.js');
    $dependencies = array_map('basename', $this->imports->sharedDependencies($source, $base));

    expect($dependencies)
        ->toContain('_floating.js')
        ->toContain('_transition.js');
});

// --- targetPath ---

it('maps a resolved dependency to its published path', function () {
    $resolved = writeFile($this->dir, '_form_errors.js', '');

    expect($this->imports->targetPath($resolved, $this->dir, '/app/resources/js/controllers'))
        ->toBe('/app/resources/js/controllers/_form_errors.js');
});
