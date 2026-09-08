<?php

use Emaia\LaravelHotwire\Components\Alert;
use Emaia\LaravelHotwire\Components\Card;
use Emaia\LaravelHotwire\Components\Item;
use Emaia\LaravelHotwire\Components\Toaster;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\SessionToast;

it('loads the component catalog', function () {
    $registry = HotwireRegistry::make();

    expect($registry->component('modal'))->not->toBeNull()
        ->and($registry->component('toast'))->not->toBeNull()
        ->and($registry->component('spinner'))->not->toBeNull();
});

it('projects the Alert family slot contract from its component class', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(Alert::SLOTS)->toBe([
        'root' => ['name' => 'alert', 'kind' => 'visual'],
        'title' => ['name' => 'alert-title', 'kind' => 'visual'],
        'description' => ['name' => 'alert-description', 'kind' => 'visual'],
        'action' => ['name' => 'alert-action', 'kind' => 'visual'],
    ])->and($catalog['components']['alert']['styling']['slots'])->toBe([
        ['class' => Alert::class],
    ])->and(HotwireRegistry::make()->component('alert')->styling->slots)->toBe([
        'alert' => 'visual',
        'alert-title' => 'visual',
        'alert-description' => 'visual',
        'alert-action' => 'visual',
    ]);
});

it('projects the Card family slot contract from its component class', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(Card::SLOTS)->toBe([
        'root' => ['name' => 'card', 'kind' => 'visual'],
        'header' => ['name' => 'card-header', 'kind' => 'visual'],
        'title' => ['name' => 'card-title', 'kind' => 'visual'],
        'description' => ['name' => 'card-description', 'kind' => 'visual'],
        'action' => ['name' => 'card-action', 'kind' => 'visual'],
        'content' => ['name' => 'card-content', 'kind' => 'visual'],
        'footer' => ['name' => 'card-footer', 'kind' => 'visual'],
    ])->and($catalog['components']['card']['styling']['slots'])->toBe([
        ['class' => Card::class],
    ])->and(HotwireRegistry::make()->component('card')->styling->slots)->toBe([
        'card' => 'visual',
        'card-header' => 'visual',
        'card-title' => 'visual',
        'card-description' => 'visual',
        'card-action' => 'visual',
        'card-content' => 'visual',
        'card-footer' => 'visual',
    ]);
});

it('projects the Item family slot contract from its component class', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(Item::SLOTS)->toBe([
        'group' => ['name' => 'item-group', 'kind' => 'visual'],
        'root' => ['name' => 'item', 'kind' => 'visual'],
        'media' => ['name' => 'item-media', 'kind' => 'visual'],
        'content' => ['name' => 'item-content', 'kind' => 'visual'],
        'title' => ['name' => 'item-title', 'kind' => 'visual'],
        'description' => ['name' => 'item-description', 'kind' => 'visual'],
        'actions' => ['name' => 'item-actions', 'kind' => 'visual'],
        'header' => ['name' => 'item-header', 'kind' => 'visual'],
        'footer' => ['name' => 'item-footer', 'kind' => 'visual'],
        'separator' => ['name' => 'item-separator', 'kind' => 'visual'],
    ])->and($catalog['components']['item']['styling']['slots'])->toBe([
        ['class' => Item::class],
    ])->and(HotwireRegistry::make()->component('item')->styling->slots)->toBe([
        'item-group' => 'visual',
        'item' => 'visual',
        'item-media' => 'visual',
        'item-content' => 'visual',
        'item-title' => 'visual',
        'item-description' => 'visual',
        'item-actions' => 'visual',
        'item-header' => 'visual',
        'item-footer' => 'visual',
        'item-separator' => 'visual',
    ]);
});

it('does not instantiate context-sensitive components while loading slot metadata', function () {
    $sessionToast = Mockery::mock(SessionToast::class);
    $sessionToast->shouldNotReceive('consume');
    app()->instance(SessionToast::class, $sessionToast);

    $catalog = require __DIR__.'/../../src/Registry/catalog.php';
    $catalog['components']['toaster']['class'] = RegistryContextSensitiveToasterFixture::class;
    $catalog['components']['toaster']['styling']['slots'] = [
        ['class' => RegistryContextSensitiveToasterFixture::class],
    ];

    expect(HotwireRegistry::fromCatalog($catalog, '/tmp')->component('toaster')->styling->slots)->toBe([
        'toaster-fixture' => 'structural',
    ]);
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

final class RegistryContextSensitiveToasterFixture extends Toaster
{
    public const array SLOTS = [
        'root' => ['name' => 'toaster-fixture', 'kind' => 'structural'],
    ];
}
