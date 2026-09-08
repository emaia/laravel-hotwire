<?php

use Emaia\LaravelHotwire\Components\Alert;
use Emaia\LaravelHotwire\Components\Card;
use Emaia\LaravelHotwire\Components\Field;
use Emaia\LaravelHotwire\Components\InputGroup;
use Emaia\LaravelHotwire\Components\Item;
use Emaia\LaravelHotwire\Components\Kbd;
use Emaia\LaravelHotwire\Components\MultiSelect;
use Emaia\LaravelHotwire\Components\Pagination;
use Emaia\LaravelHotwire\Components\Sidebar;
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

it('projects the Field family slot contract from its component class', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';
    $registry = HotwireRegistry::make();

    expect(Field::SLOTS)->toBe([
        'set' => ['name' => 'field-set', 'kind' => 'visual'],
        'legend' => ['name' => 'field-legend', 'kind' => 'visual'],
        'group' => ['name' => 'field-group', 'kind' => 'visual'],
        'root' => ['name' => 'field', 'kind' => 'visual'],
        'label' => ['name' => 'field-label', 'kind' => 'visual'],
        'content' => ['name' => 'field-content', 'kind' => 'visual'],
        'title' => ['name' => 'field-title', 'kind' => 'visual'],
        'description' => ['name' => 'field-description', 'kind' => 'visual'],
        'error' => ['name' => 'field-error', 'kind' => 'visual'],
        'separator' => ['name' => 'field-separator', 'kind' => 'visual'],
        'separator-line' => ['name' => 'field-separator-line', 'kind' => 'visual'],
        'separator-content' => ['name' => 'field-separator-content', 'kind' => 'visual'],
        'label-required' => ['name' => 'field-label-required', 'kind' => 'structural'],
    ])->and($catalog['components']['field']['styling']['slots'])->toBe([
        ['class' => Field::class],
    ])->and($catalog['components']['field.error']['styling']['slots'])->toBe([
        ['class' => Field::class, 'only' => ['error']],
    ])->and($catalog['components']['field.group']['styling']['slots'])->toBe([
        ['class' => Field::class, 'only' => ['group']],
    ])->and($catalog['components']['field.label']['styling']['slots'])->toBe([
        ['class' => Field::class, 'only' => ['label', 'label-required']],
    ])->and($registry->component('field')->styling->slots)->toBe([
        'field-set' => 'visual',
        'field-legend' => 'visual',
        'field-group' => 'visual',
        'field' => 'visual',
        'field-label' => 'visual',
        'field-content' => 'visual',
        'field-title' => 'visual',
        'field-description' => 'visual',
        'field-error' => 'visual',
        'field-separator' => 'visual',
        'field-separator-line' => 'visual',
        'field-separator-content' => 'visual',
        'field-label-required' => 'structural',
    ])->and($registry->component('field.error')->styling->slots)->toBe([
        'field-error' => 'visual',
    ])->and($registry->component('field.group')->styling->slots)->toBe([
        'field-group' => 'visual',
    ])->and($registry->component('field.label')->styling->slots)->toBe([
        'field-label' => 'visual',
        'field-label-required' => 'structural',
    ]);
});

it('projects the Input Group family slot contract from its component class', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(InputGroup::SLOTS)->toBe([
        'root' => ['name' => 'input-group', 'kind' => 'visual'],
        'addon' => ['name' => 'input-group-addon', 'kind' => 'visual'],
        'control' => ['name' => 'input-group-control', 'kind' => 'visual'],
    ])->and($catalog['components']['input-group']['styling']['slots'])->toBe([
        ['class' => InputGroup::class],
    ])->and(HotwireRegistry::make()->component('input-group')->styling->slots)->toBe([
        'input-group' => 'visual',
        'input-group-addon' => 'visual',
        'input-group-control' => 'visual',
    ]);
});

it('projects the Kbd family slot contract from its component class', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(Kbd::SLOTS)->toBe([
        'root' => ['name' => 'kbd', 'kind' => 'visual'],
        'group' => ['name' => 'kbd-group', 'kind' => 'visual'],
    ])->and($catalog['components']['kbd']['styling']['slots'])->toBe([
        ['class' => Kbd::class],
    ])->and(HotwireRegistry::make()->component('kbd')->styling->slots)->toBe([
        'kbd' => 'visual',
        'kbd-group' => 'visual',
    ]);
});

it('projects the Multi Select family slot contract from its component class', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(MultiSelect::SLOTS)->toBe([
        'root' => ['name' => 'multi-select', 'kind' => 'visual'],
        'native' => ['name' => 'multi-select-native', 'kind' => 'visual'],
        'validation' => ['name' => 'multi-select-validation', 'kind' => 'visual'],
        'trigger' => ['name' => 'multi-select-trigger', 'kind' => 'visual'],
        'value' => ['name' => 'multi-select-value', 'kind' => 'visual'],
        'trigger-icon' => ['name' => 'multi-select-trigger-icon', 'kind' => 'visual'],
        'content' => ['name' => 'multi-select-content', 'kind' => 'visual'],
        'search' => ['name' => 'multi-select-search', 'kind' => 'visual'],
        'search-icon' => ['name' => 'multi-select-search-icon', 'kind' => 'visual'],
        'select-all' => ['name' => 'multi-select-select-all', 'kind' => 'visual'],
        'indicator' => ['name' => 'multi-select-indicator', 'kind' => 'visual'],
        'option-text' => ['name' => 'multi-select-option-text', 'kind' => 'visual'],
        'list' => ['name' => 'multi-select-list', 'kind' => 'visual'],
        'option' => ['name' => 'multi-select-option', 'kind' => 'visual'],
        'empty' => ['name' => 'multi-select-empty', 'kind' => 'visual'],
    ])->and($catalog['components']['multi-select']['styling']['slots'])->toBe([
        ['class' => MultiSelect::class],
    ])->and(HotwireRegistry::make()->component('multi-select')->styling->slots)->toBe([
        'multi-select' => 'visual',
        'multi-select-native' => 'visual',
        'multi-select-validation' => 'visual',
        'multi-select-trigger' => 'visual',
        'multi-select-value' => 'visual',
        'multi-select-trigger-icon' => 'visual',
        'multi-select-content' => 'visual',
        'multi-select-search' => 'visual',
        'multi-select-search-icon' => 'visual',
        'multi-select-select-all' => 'visual',
        'multi-select-indicator' => 'visual',
        'multi-select-option-text' => 'visual',
        'multi-select-list' => 'visual',
        'multi-select-option' => 'visual',
        'multi-select-empty' => 'visual',
    ]);
});

it('projects the Pagination family slot contract from its component class', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(Pagination::SLOTS)->toBe([
        'root' => ['name' => 'pagination', 'kind' => 'visual'],
        'content' => ['name' => 'pagination-content', 'kind' => 'visual'],
        'item' => ['name' => 'pagination-item', 'kind' => 'visual'],
        'link' => ['name' => 'pagination-link', 'kind' => 'visual'],
        'previous' => ['name' => 'pagination-previous', 'kind' => 'visual'],
        'previous-label' => ['name' => 'pagination-previous-label', 'kind' => 'visual'],
        'next' => ['name' => 'pagination-next', 'kind' => 'visual'],
        'next-content' => ['name' => 'pagination-next-content', 'kind' => 'visual'],
        'next-label' => ['name' => 'pagination-next-label', 'kind' => 'visual'],
        'next-loading-content' => ['name' => 'pagination-next-loading-content', 'kind' => 'visual'],
        'next-loading-label' => ['name' => 'pagination-next-loading-label', 'kind' => 'visual'],
        'next-spinner' => ['name' => 'pagination-next-spinner', 'kind' => 'visual'],
        'next-icon' => ['name' => 'pagination-next-icon', 'kind' => 'visual'],
        'ellipsis' => ['name' => 'pagination-ellipsis', 'kind' => 'visual'],
        'status' => ['name' => 'pagination-status', 'kind' => 'structural'],
    ])->and($catalog['components']['pagination']['styling']['slots'])->toBe([
        ['class' => Pagination::class],
    ])->and(HotwireRegistry::make()->component('pagination')->styling->slots)->toBe([
        'pagination' => 'visual',
        'pagination-content' => 'visual',
        'pagination-item' => 'visual',
        'pagination-link' => 'visual',
        'pagination-previous' => 'visual',
        'pagination-previous-label' => 'visual',
        'pagination-next' => 'visual',
        'pagination-next-content' => 'visual',
        'pagination-next-label' => 'visual',
        'pagination-next-loading-content' => 'visual',
        'pagination-next-loading-label' => 'visual',
        'pagination-next-spinner' => 'visual',
        'pagination-next-icon' => 'visual',
        'pagination-ellipsis' => 'visual',
        'pagination-status' => 'structural',
    ]);
});

it('projects the Sidebar family slot contract from its component class', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(Sidebar::SLOTS)->toBe([
        'wrapper' => ['name' => 'sidebar-wrapper', 'kind' => 'visual'],
        'root' => ['name' => 'sidebar', 'kind' => 'visual'],
        'backdrop' => ['name' => 'sidebar-backdrop', 'kind' => 'visual'],
        'trigger' => ['name' => 'sidebar-trigger', 'kind' => 'visual'],
        'rail' => ['name' => 'sidebar-rail', 'kind' => 'visual'],
        'inset' => ['name' => 'sidebar-inset', 'kind' => 'visual'],
        'header' => ['name' => 'sidebar-header', 'kind' => 'visual'],
        'brand' => ['name' => 'sidebar-brand', 'kind' => 'visual'],
        'brand-logo' => ['name' => 'sidebar-brand-logo', 'kind' => 'visual'],
        'brand-icon' => ['name' => 'sidebar-brand-icon', 'kind' => 'visual'],
        'footer' => ['name' => 'sidebar-footer', 'kind' => 'visual'],
        'content' => ['name' => 'sidebar-content', 'kind' => 'visual'],
        'input' => ['name' => 'sidebar-input', 'kind' => 'visual'],
        'separator' => ['name' => 'sidebar-separator', 'kind' => 'visual'],
        'group' => ['name' => 'sidebar-group', 'kind' => 'visual'],
        'group-label' => ['name' => 'sidebar-group-label', 'kind' => 'visual'],
        'group-action' => ['name' => 'sidebar-group-action', 'kind' => 'visual'],
        'group-content' => ['name' => 'sidebar-group-content', 'kind' => 'visual'],
        'menu' => ['name' => 'sidebar-menu', 'kind' => 'visual'],
        'menu-item' => ['name' => 'sidebar-menu-item', 'kind' => 'visual'],
        'menu-button' => ['name' => 'sidebar-menu-button', 'kind' => 'visual'],
        'menu-action' => ['name' => 'sidebar-menu-action', 'kind' => 'visual'],
        'menu-badge' => ['name' => 'sidebar-menu-badge', 'kind' => 'visual'],
        'menu-skeleton' => ['name' => 'sidebar-menu-skeleton', 'kind' => 'visual'],
        'menu-skeleton-icon' => ['name' => 'sidebar-menu-skeleton-icon', 'kind' => 'visual'],
        'menu-skeleton-text' => ['name' => 'sidebar-menu-skeleton-text', 'kind' => 'visual'],
        'menu-sub' => ['name' => 'sidebar-menu-sub', 'kind' => 'visual'],
        'menu-sub-item' => ['name' => 'sidebar-menu-sub-item', 'kind' => 'visual'],
        'menu-sub-button' => ['name' => 'sidebar-menu-sub-button', 'kind' => 'visual'],
        'gap' => ['name' => 'sidebar-gap', 'kind' => 'visual'],
        'container' => ['name' => 'sidebar-container', 'kind' => 'visual'],
        'inner' => ['name' => 'sidebar-inner', 'kind' => 'visual'],
    ])->and($catalog['components']['sidebar']['styling']['slots'])->toBe([
        ['class' => Sidebar::class],
    ])->and(HotwireRegistry::make()->component('sidebar')->styling->slots)->toBe([
        'sidebar-wrapper' => 'visual',
        'sidebar' => 'visual',
        'sidebar-backdrop' => 'visual',
        'sidebar-trigger' => 'visual',
        'sidebar-rail' => 'visual',
        'sidebar-inset' => 'visual',
        'sidebar-header' => 'visual',
        'sidebar-brand' => 'visual',
        'sidebar-brand-logo' => 'visual',
        'sidebar-brand-icon' => 'visual',
        'sidebar-footer' => 'visual',
        'sidebar-content' => 'visual',
        'sidebar-input' => 'visual',
        'sidebar-separator' => 'visual',
        'sidebar-group' => 'visual',
        'sidebar-group-label' => 'visual',
        'sidebar-group-action' => 'visual',
        'sidebar-group-content' => 'visual',
        'sidebar-menu' => 'visual',
        'sidebar-menu-item' => 'visual',
        'sidebar-menu-button' => 'visual',
        'sidebar-menu-action' => 'visual',
        'sidebar-menu-badge' => 'visual',
        'sidebar-menu-skeleton' => 'visual',
        'sidebar-menu-skeleton-icon' => 'visual',
        'sidebar-menu-skeleton-text' => 'visual',
        'sidebar-menu-sub' => 'visual',
        'sidebar-menu-sub-item' => 'visual',
        'sidebar-menu-sub-button' => 'visual',
        'sidebar-gap' => 'visual',
        'sidebar-container' => 'visual',
        'sidebar-inner' => 'visual',
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
