<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;

it('loads the component catalog', function () {
    $registry = HotwireRegistry::make();

    expect($registry->component('modal'))->not->toBeNull()
        ->and($registry->component('toast'))->not->toBeNull()
        ->and($registry->component('spinner'))->not->toBeNull();
});

it('loads the controller catalog', function () {
    $registry = HotwireRegistry::make();

    expect($registry->controller('modal'))->not->toBeNull()
        ->and($registry->controller('tooltip'))->not->toBeNull()
        ->and($registry->controller('turbo--morph-guard'))->not->toBeNull()
        ->and($registry->controller('turbo--progress'))->not->toBeNull();
});

it('registers Timeago without npm dependencies', function () {
    expect(HotwireRegistry::make()->controller('timeago')->npm)->toBe([]);
});

it('keeps component controller dependencies in the registry', function () {
    $registry = HotwireRegistry::make();

    expect(array_map(
        fn ($controller) => $controller->identifier,
        $registry->controllersForComponent('toast'),
    ))->toBe(['toast']);
});

it('points every registered component class, view, docs and controller source to a real file', function () {
    $registry = HotwireRegistry::make();
    $basePath = $registry->basePath();

    foreach ($registry->components() as $component) {
        expect(class_exists($component->class))->toBeTrue();

        $view = str_replace(['hotwire::', '.'], ['resources/views/', '/'], $component->view).'.blade.php';

        expect(file_exists($basePath.'/'.$view))->toBeTrue()
            ->and(file_exists($basePath.'/'.$component->docs))->toBeTrue();

        foreach ($registry->controllersForComponent($component) as $controller) {
            expect(file_exists($controller->sourcePath($basePath)))->toBeTrue();
            expect(file_exists($basePath.'/'.$controller->docs))->toBeTrue();
        }
    }
});

it('points every registered controller source and docs path to a real file', function () {
    $registry = HotwireRegistry::make();
    $basePath = $registry->basePath();

    foreach ($registry->controllers() as $controller) {
        expect(file_exists($controller->sourcePath($basePath)))->toBeTrue();
        expect(file_exists($basePath.'/'.$controller->docs))->toBeTrue();
    }
});

it('rejects a category outside the shared vocabulary', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';
    $catalog['controllers']['tooltip']['category'] = 'made-up';

    expect(fn () => HotwireRegistry::fromCatalog($catalog, '/tmp'))->toThrow(ValueError::class);
});

it('keeps a component and the controllers it mounts in the same category', function () {
    $registry = HotwireRegistry::make();

    foreach ($registry->components() as $key => $component) {
        $controller = $registry->controller($key);

        if ($controller === null) {
            continue;
        }

        expect($controller->category)->toBe(
            $component->category,
            "Component \"{$key}\" is [{$component->category->value}] but its controller is [{$controller->category->value}]",
        );
    }
});

it('every controller and component has a non-empty description', function () {
    $registry = HotwireRegistry::make();

    foreach ($registry->controllers() as $identifier => $controller) {
        expect($controller->description)
            ->not->toBeEmpty("Controller \"{$identifier}\" is missing a description");
    }

    foreach ($registry->components() as $key => $component) {
        expect($component->description)
            ->not->toBeEmpty("Component \"{$key}\" is missing a description");
    }
});

it('keeps catalog entries alphabetized by key', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    foreach (['components', 'controllers'] as $section) {
        $keys = array_keys($catalog[$section]);
        $sorted = $keys;
        sort($sorted);

        expect($keys)->toBe($sorted, "Catalog section [{$section}] is not alphabetized.");
    }
});
