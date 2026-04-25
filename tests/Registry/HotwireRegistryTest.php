<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;

it('loads the component catalog', function () {
    $registry = HotwireRegistry::make();

    expect($registry->component('dialog'))->not->toBeNull()
        ->and($registry->component('flash-message'))->not->toBeNull()
        ->and($registry->component('loader'))->not->toBeNull();
});

it('loads the controller catalog', function () {
    $registry = HotwireRegistry::make();

    expect($registry->controller('dialog'))->not->toBeNull()
        ->and($registry->controller('tooltip'))->not->toBeNull()
        ->and($registry->controller('turbo--progress'))->not->toBeNull();
});

it('keeps component controller dependencies in the registry', function () {
    $registry = HotwireRegistry::make();

    expect(array_map(
        fn ($controller) => $controller->identifier,
        $registry->controllersForComponent('flash-message'),
    ))->toBe(['toast']);
});

it('points every registered component class, docs and controller source to a real file', function () {
    $registry = HotwireRegistry::make();
    $basePath = $registry->basePath();

    foreach ($registry->components() as $component) {
        expect(class_exists($component->class))->toBeTrue();
        expect(file_exists($basePath.'/'.$component->docs))->toBeTrue();

        foreach ($registry->controllersForComponent($component) as $controller) {
            expect(file_exists($controller->sourcePath($basePath)))->toBeTrue();
            expect(file_exists($basePath.'/'.$controller->docs))->toBeTrue();
        }
    }
});

it('points every registered controller source and docs path to a real file', function () {
    $registry = HotwireRegistry::make();
    $basePath = $registry->basePath();
    $validCategories = ['overlay', 'feedback', 'forms', 'turbo', 'utility', 'dev'];

    foreach ($registry->controllers() as $controller) {
        expect(file_exists($controller->sourcePath($basePath)))->toBeTrue();
        expect(file_exists($basePath.'/'.$controller->docs))->toBeTrue();
        expect($controller->category)->toBeIn($validCategories);
    }
});
