<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->appBase = isolateAppPaths();
});

afterEach(function () {
    releaseIsolatedAppPaths($this->appBase);
});

it('generates ide json with package and app stimulus controller locations', function () {
    File::ensureDirectoryExists(resource_path('js/controllers/admin'));
    File::put(resource_path('js/controllers/gallery_controller.js'), '// gallery');
    File::put(resource_path('js/controllers/admin/photo_grid_controller.ts'), '// grid');

    $this->artisan('hotwire:ide-json')->assertSuccessful();

    $json = json_decode(File::get(base_path('ide.json')), true, 512, JSON_THROW_ON_ERROR);
    $locations = $json['completions'][0]['options']['stringsWithLocation'];

    expect($locations['chart'])->toBe('vendor/emaia/laravel-hotwire/resources/js/controllers/chart_controller.js')
        ->and($locations['gallery'])->toBe('resources/js/controllers/gallery_controller.js')
        ->and($locations['admin--photo-grid'])->toBe('resources/js/controllers/admin/photo_grid_controller.ts');
});

it('lets app controllers override package controller locations', function () {
    File::ensureDirectoryExists(resource_path('js/controllers'));
    File::put(resource_path('js/controllers/chart_controller.js'), '// local chart');

    $this->artisan('hotwire:ide-json')->assertSuccessful();

    $json = json_decode(File::get(base_path('ide.json')), true, 512, JSON_THROW_ON_ERROR);
    $locations = $json['completions'][0]['options']['stringsWithLocation'];

    expect($locations['chart'])->toBe('resources/js/controllers/chart_controller.js');
});

it('merges hotwire metadata into an existing ide json', function () {
    File::put(base_path('ide.json'), json_encode([
        '$schema' => 'https://laravel-ide.com/schema/laravel-ide-v2.json',
        'blade' => [
            'components' => [
                'list' => [
                    ['name' => 'panel', 'namespace' => 'app', 'className' => '\\App\\View\\Components\\Panel'],
                    ['name' => 'old-button', 'namespace' => 'hw', 'className' => '\\Old\\Button'],
                ],
                'phpNamespaces' => [
                    ['phpNamespace' => '\\App\\View\\Components', 'prefix' => 'app:', 'ignoreBladeComponentPrefix' => true],
                ],
            ],
        ],
        'completions' => [
            [
                'complete' => 'staticStrings',
                'condition' => [['functionNames' => ['foo'], 'parameters' => [1]]],
                'options' => ['strings' => ['bar']],
            ],
        ],
    ], JSON_PRETTY_PRINT));

    $this->artisan('hotwire:ide-json')->assertSuccessful();

    $json = json_decode(File::get(base_path('ide.json')), true, 512, JSON_THROW_ON_ERROR);
    $components = collect($json['blade']['components']['list']);

    expect($components->contains(fn (array $component): bool => $component['namespace'] === 'app' && $component['name'] === 'panel'))->toBeTrue()
        ->and($components->contains(fn (array $component): bool => $component['namespace'] === 'hw' && $component['name'] === 'old-button'))->toBeTrue()
        ->and($components->contains(fn (array $component): bool => $component['namespace'] === 'hw' && $component['name'] === 'button'))->toBeFalse()
        ->and($json['completions'])->toHaveCount(8);
});
