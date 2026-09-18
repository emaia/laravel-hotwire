<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Emaia\LaravelHotwire\Support\LaravelIdeaMetadata;

it('keeps the checked-in ide json in sync with generated metadata', function () {
    $expected = LaravelIdeaMetadata::make();
    $actual = json_decode(file_get_contents(__DIR__.'/../../ide.json'), true, 512, JSON_THROW_ON_ERROR);

    expect($actual)->toBe($expected);
});

it('includes every component and subcomponent in the hw namespace', function () {
    $metadata = LaravelIdeaMetadata::make();
    $components = collect($metadata['blade']['components']['list']);
    $names = $components->pluck('name')->all();

    foreach (HotwireRegistry::make()->components() as $component) {
        expect($names)->toContain($component->key);
    }

    foreach (array_keys(ComponentAliases::subComponents()) as $alias) {
        expect($names)->toContain($alias);
    }

    expect($components->pluck('namespace')->unique()->values()->all())->toBe(['hw']);
});

it('registers renamed components under Laravel Idea derived public names', function () {
    $metadata = LaravelIdeaMetadata::make();
    $components = collect($metadata['blade']['components']['list']);

    expect($components->firstWhere('name', 'empty-state'))
        ->toMatchArray([
            'name' => 'empty-state',
            'namespace' => 'hw',
            'className' => '\Emaia\LaravelHotwire\Components\EmptyState',
        ])
        ->and($components->firstWhere('name', 'field.set'))->toMatchArray([
            'name' => 'field.set',
            'namespace' => 'hw',
            'className' => '\Emaia\LaravelHotwire\Components\Field\Set',
        ])
        ->and($components->pluck('name')->all())->not->toContain('empty')
        ->and($components->pluck('name')->all())->not->toContain('field.field-set');
});

it('omits stimulus controller completions from the package ide json', function () {
    $metadata = LaravelIdeaMetadata::make();

    expect($metadata)->not->toHaveKey('completions');
});

it('can generate stimulus controller completions for app-level ide json', function () {
    $metadata = LaravelIdeaMetadata::make(includeComponents: false, includeCompletions: true);
    $locations = $metadata['completions'][0]['options']['stringsWithLocation'];

    foreach (HotwireRegistry::make()->controllers() as $identifier => $controller) {
        $source = __DIR__.'/../../'.$controller->source;
        $path = 'vendor/emaia/laravel-hotwire/'.$controller->source;
        $lines = file($source, FILE_IGNORE_NEW_LINES);
        $line = (int) substr($locations[$identifier], strlen($path) + 1);

        expect($locations)->toHaveKey($identifier)
            ->and($locations[$identifier])->toMatch('/^'.preg_quote($path, '/').':\d+$/')
            ->and(trim($lines[$line - 1]))->toStartWith('export default class');
    }

    expect($metadata)->not->toHaveKey('blade');
});

it('configures short hw tags without registering legacy short prefixes', function () {
    $metadata = LaravelIdeaMetadata::make();

    expect($metadata['blade']['components']['phpNamespaces'])->toBe([
        [
            'phpNamespace' => '\\Emaia\\LaravelHotwire\\Components',
            'prefix' => 'hw:',
            'ignoreBladeComponentPrefix' => true,
        ],
    ]);
});
