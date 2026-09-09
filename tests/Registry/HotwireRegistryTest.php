<?php

use Emaia\LaravelHotwire\Components\Alert;
use Emaia\LaravelHotwire\Components\AlertDialog;
use Emaia\LaravelHotwire\Components\AspectRatio;
use Emaia\LaravelHotwire\Components\Avatar;
use Emaia\LaravelHotwire\Components\BackToTop;
use Emaia\LaravelHotwire\Components\Badge;
use Emaia\LaravelHotwire\Components\Breadcrumb;
use Emaia\LaravelHotwire\Components\Button;
use Emaia\LaravelHotwire\Components\ButtonGroup;
use Emaia\LaravelHotwire\Components\Card;
use Emaia\LaravelHotwire\Components\Checkbox;
use Emaia\LaravelHotwire\Components\CheckboxGroup;
use Emaia\LaravelHotwire\Components\ConditionalField;
use Emaia\LaravelHotwire\Components\Drawer;
use Emaia\LaravelHotwire\Components\EmptyState;
use Emaia\LaravelHotwire\Components\Field;
use Emaia\LaravelHotwire\Components\File;
use Emaia\LaravelHotwire\Components\Form;
use Emaia\LaravelHotwire\Components\Icon;
use Emaia\LaravelHotwire\Components\Input;
use Emaia\LaravelHotwire\Components\InputGroup;
use Emaia\LaravelHotwire\Components\Item;
use Emaia\LaravelHotwire\Components\Kbd;
use Emaia\LaravelHotwire\Components\Modal;
use Emaia\LaravelHotwire\Components\MultiSelect;
use Emaia\LaravelHotwire\Components\Navbar;
use Emaia\LaravelHotwire\Components\Pagination;
use Emaia\LaravelHotwire\Components\Progress;
use Emaia\LaravelHotwire\Components\RadioGroup;
use Emaia\LaravelHotwire\Components\ScrollProgress;
use Emaia\LaravelHotwire\Components\Select;
use Emaia\LaravelHotwire\Components\Separator;
use Emaia\LaravelHotwire\Components\Sheet;
use Emaia\LaravelHotwire\Components\Sidebar;
use Emaia\LaravelHotwire\Components\Skeleton;
use Emaia\LaravelHotwire\Components\Slider;
use Emaia\LaravelHotwire\Components\Spinner;
use Emaia\LaravelHotwire\Components\Sticky;
use Emaia\LaravelHotwire\Components\SwitchInput;
use Emaia\LaravelHotwire\Components\Table;
use Emaia\LaravelHotwire\Components\Tabs;
use Emaia\LaravelHotwire\Components\Textarea;
use Emaia\LaravelHotwire\Components\Timeago;
use Emaia\LaravelHotwire\Components\Toaster;
use Emaia\LaravelHotwire\Components\Toggle;
use Emaia\LaravelHotwire\Components\ToggleGroup;
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

it('projects the Alert Dialog family slot contract from its component class', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(AlertDialog::SLOTS)->toBe([
        'overlay' => ['name' => 'alert-dialog-overlay', 'kind' => 'visual'],
        'backdrop' => ['name' => 'alert-dialog-backdrop', 'kind' => 'visual'],
        'panel' => ['name' => 'alert-dialog-panel', 'kind' => 'visual'],
        'header' => ['name' => 'alert-dialog-header', 'kind' => 'visual'],
        'title' => ['name' => 'alert-dialog-title', 'kind' => 'visual'],
        'description' => ['name' => 'alert-dialog-description', 'kind' => 'visual'],
        'body' => ['name' => 'alert-dialog-body', 'kind' => 'visual'],
        'footer' => ['name' => 'alert-dialog-footer', 'kind' => 'visual'],
        'cancel' => ['name' => 'alert-dialog-cancel', 'kind' => 'visual'],
        'action' => ['name' => 'alert-dialog-action', 'kind' => 'visual'],
        'root' => ['name' => 'alert-dialog', 'kind' => 'structural'],
        'trigger' => ['name' => 'alert-dialog-trigger', 'kind' => 'structural'],
    ])->and($catalog['components']['alert-dialog']['styling']['slots'])->toBe([
        ['class' => AlertDialog::class],
    ])->and(HotwireRegistry::make()->component('alert-dialog')->styling->slots)->toBe([
        'alert-dialog-overlay' => 'visual',
        'alert-dialog-backdrop' => 'visual',
        'alert-dialog-panel' => 'visual',
        'alert-dialog-header' => 'visual',
        'alert-dialog-title' => 'visual',
        'alert-dialog-description' => 'visual',
        'alert-dialog-body' => 'visual',
        'alert-dialog-footer' => 'visual',
        'alert-dialog-cancel' => 'visual',
        'alert-dialog-action' => 'visual',
        'alert-dialog' => 'structural',
        'alert-dialog-trigger' => 'structural',
    ]);
});

it('projects singleton primitive slot contracts from their component classes', function (string $key, string $class, string $name, string $kind) {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect($class::SLOTS)->toBe([
        'root' => ['name' => $name, 'kind' => $kind],
    ])->and($catalog['components'][$key]['styling']['slots'])->toBe([
        ['class' => $class],
    ])->and(HotwireRegistry::make()->component($key)->styling->slots)->toBe([
        $name => $kind,
    ]);
})->with([
    'aspect ratio' => ['aspect-ratio', AspectRatio::class, 'aspect-ratio', 'structural'],
    'back to top' => ['back-to-top', BackToTop::class, 'back-to-top', 'visual'],
    'badge' => ['badge', Badge::class, 'badge', 'visual'],
    'button' => ['button', Button::class, 'button', 'visual'],
    'checkbox' => ['checkbox', Checkbox::class, 'checkbox', 'visual'],
    'icon' => ['icon', Icon::class, 'icon', 'visual'],
    'scroll progress' => ['scroll-progress', ScrollProgress::class, 'scroll-progress', 'visual'],
    'separator' => ['separator', Separator::class, 'separator', 'visual'],
    'skeleton' => ['skeleton', Skeleton::class, 'skeleton', 'visual'],
    'slider' => ['slider', Slider::class, 'slider', 'visual'],
    'spinner' => ['spinner', Spinner::class, 'spinner', 'visual'],
    'sticky' => ['sticky', Sticky::class, 'sticky', 'visual'],
    'switch' => ['switch', SwitchInput::class, 'switch', 'visual'],
    'timeago' => ['timeago', Timeago::class, 'timeago', 'visual'],
    'toggle' => ['toggle', Toggle::class, 'toggle', 'visual'],
]);

it('projects the Textarea family slot contract from its component class', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(Textarea::SLOTS)->toBe([
        'wrapper' => ['name' => 'textarea-wrapper', 'kind' => 'visual'],
        'root' => ['name' => 'textarea', 'kind' => 'visual'],
    ])->and($catalog['components']['textarea']['styling']['slots'])->toBe([
        ['class' => Textarea::class],
    ])->and(HotwireRegistry::make()->component('textarea')->styling->slots)->toBe([
        'textarea-wrapper' => 'visual',
        'textarea' => 'visual',
    ]);
});

it('projects form control slot contracts from their component classes', function (string $key, string $class) {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';
    $slots = match ($class) {
        ConditionalField::class => [
            'root' => ['name' => 'conditional-field', 'kind' => 'structural'],
        ],
        File::class => [
            'wrapper' => ['name' => 'file-wrapper', 'kind' => 'visual'],
            'input' => ['name' => 'file-input', 'kind' => 'visual'],
        ],
        Form::class => [
            'root' => ['name' => 'form', 'kind' => 'structural'],
        ],
        Input::class => [
            'wrapper' => ['name' => 'input-wrapper', 'kind' => 'visual'],
            'root' => ['name' => 'input', 'kind' => 'visual'],
            'clear-button' => ['name' => 'clear-input-button', 'kind' => 'visual'],
        ],
        Select::class => [
            'wrapper' => ['name' => 'select-wrapper', 'kind' => 'visual'],
            'root' => ['name' => 'select', 'kind' => 'visual'],
            'icon' => ['name' => 'select-icon', 'kind' => 'visual'],
        ],
    };
    $resolved = [];

    foreach ($slots as $slot) {
        $resolved[$slot['name']] = $slot['kind'];
    }

    expect($class::SLOTS)->toBe($slots)
        ->and($catalog['components'][$key]['styling']['slots'])->toBe([
            ['class' => $class],
        ])->and(HotwireRegistry::make()->component($key)->styling->slots)->toBe($resolved);
})->with([
    'conditional field' => ['conditional-field', ConditionalField::class],
    'file' => ['file', File::class],
    'form' => ['form', Form::class],
    'input' => ['input', Input::class],
    'select' => ['select', Select::class],
]);

it('projects choice group slots from one declaration per family', function (string $key, string $class) {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';
    $slots = [
        'root' => ['name' => $key, 'kind' => 'visual'],
        'item' => ['name' => $key.'-item', 'kind' => 'visual'],
        'input' => ['name' => $key.'-input', 'kind' => 'visual'],
        'item-content' => ['name' => $key.'-item-content', 'kind' => 'visual'],
    ];

    expect($class::SLOTS)->toBe($slots)
        ->and($catalog['components'][$key]['styling']['slots'])->toBe([
            ['class' => $class],
        ])->and($catalog['components'][$key.'.item']['styling']['slots'])->toBe([
            ['class' => $class, 'only' => ['item', 'input', 'item-content']],
        ])->and(HotwireRegistry::make()->component($key)->styling->slots)->toBe([
            $key => 'visual',
            $key.'-item' => 'visual',
            $key.'-input' => 'visual',
            $key.'-item-content' => 'visual',
        ])->and(HotwireRegistry::make()->component($key.'.item')->styling->slots)->toBe([
            $key.'-item' => 'visual',
            $key.'-input' => 'visual',
            $key.'-item-content' => 'visual',
        ]);
})->with([
    'checkbox group' => ['checkbox-group', CheckboxGroup::class],
    'radio group' => ['radio-group', RadioGroup::class],
]);

it('projects Navbar slots and its Sticky reference without duplicate ownership', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(Navbar::SLOTS)->toBe([
        'root' => ['name' => 'navbar', 'kind' => 'visual'],
        'item' => ['name' => 'navbar-item', 'kind' => 'visual'],
    ])->and($catalog['components']['navbar']['styling']['slots'])->toBe([
        ['class' => Navbar::class],
        ['class' => Sticky::class, 'only' => ['root']],
    ])->and($catalog['components']['navbar.item']['styling']['slots'])->toBe([
        ['class' => Navbar::class, 'only' => ['item']],
    ])->and(HotwireRegistry::make()->component('navbar')->styling->slots)->toBe([
        'navbar' => 'visual',
        'navbar-item' => 'visual',
        'sticky' => 'visual',
    ])->and(HotwireRegistry::make()->component('navbar.item')->styling->slots)->toBe([
        'navbar-item' => 'visual',
    ]);
});

it('projects composed family slot contracts from their root classes', function (string $key, string $class) {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';
    $slots = match ($class) {
        Avatar::class => [
            'root' => ['name' => 'avatar', 'kind' => 'visual'],
            'image' => ['name' => 'avatar-image', 'kind' => 'visual'],
            'fallback' => ['name' => 'avatar-fallback', 'kind' => 'visual'],
            'badge' => ['name' => 'avatar-badge', 'kind' => 'visual'],
            'group' => ['name' => 'avatar-group', 'kind' => 'visual'],
            'group-count' => ['name' => 'avatar-group-count', 'kind' => 'visual'],
        ],
        Breadcrumb::class => [
            'root' => ['name' => 'breadcrumb', 'kind' => 'visual'],
            'list' => ['name' => 'breadcrumb-list', 'kind' => 'visual'],
            'item' => ['name' => 'breadcrumb-item', 'kind' => 'visual'],
            'link' => ['name' => 'breadcrumb-link', 'kind' => 'visual'],
            'page' => ['name' => 'breadcrumb-page', 'kind' => 'visual'],
            'separator' => ['name' => 'breadcrumb-separator', 'kind' => 'visual'],
            'ellipsis' => ['name' => 'breadcrumb-ellipsis', 'kind' => 'visual'],
        ],
        ButtonGroup::class => [
            'root' => ['name' => 'button-group', 'kind' => 'visual'],
            'separator' => ['name' => 'button-group-separator', 'kind' => 'visual'],
            'text' => ['name' => 'button-group-text', 'kind' => 'visual'],
        ],
        EmptyState::class => [
            'root' => ['name' => 'empty-state', 'kind' => 'visual'],
            'header' => ['name' => 'empty-state-header', 'kind' => 'visual'],
            'media' => ['name' => 'empty-state-media', 'kind' => 'visual'],
            'title' => ['name' => 'empty-state-title', 'kind' => 'visual'],
            'description' => ['name' => 'empty-state-description', 'kind' => 'visual'],
            'content' => ['name' => 'empty-state-content', 'kind' => 'visual'],
        ],
        Progress::class => [
            'root' => ['name' => 'progress', 'kind' => 'visual'],
            'track' => ['name' => 'progress-track', 'kind' => 'visual'],
            'indicator' => ['name' => 'progress-indicator', 'kind' => 'visual'],
            'label' => ['name' => 'progress-label', 'kind' => 'visual'],
            'value' => ['name' => 'progress-value', 'kind' => 'visual'],
        ],
        Table::class => [
            'container' => ['name' => 'table-container', 'kind' => 'visual'],
            'root' => ['name' => 'table', 'kind' => 'visual'],
            'header' => ['name' => 'table-header', 'kind' => 'visual'],
            'body' => ['name' => 'table-body', 'kind' => 'visual'],
            'footer' => ['name' => 'table-footer', 'kind' => 'visual'],
            'row' => ['name' => 'table-row', 'kind' => 'visual'],
            'head' => ['name' => 'table-head', 'kind' => 'visual'],
            'cell' => ['name' => 'table-cell', 'kind' => 'visual'],
            'caption' => ['name' => 'table-caption', 'kind' => 'visual'],
        ],
        Tabs::class => [
            'root' => ['name' => 'tabs', 'kind' => 'visual'],
            'list' => ['name' => 'tabs-list', 'kind' => 'visual'],
            'trigger' => ['name' => 'tabs-trigger', 'kind' => 'visual'],
            'panel' => ['name' => 'tabs-panel', 'kind' => 'visual'],
        ],
    };
    $resolved = [];

    foreach ($slots as $slot) {
        $resolved[$slot['name']] = $slot['kind'];
    }

    expect($class::SLOTS)->toBe($slots)
        ->and($catalog['components'][$key]['styling']['slots'])->toBe([
            ['class' => $class],
        ])->and(HotwireRegistry::make()->component($key)->styling->slots)->toBe($resolved);
})->with([
    'avatar' => ['avatar', Avatar::class],
    'breadcrumb' => ['breadcrumb', Breadcrumb::class],
    'button group' => ['button-group', ButtonGroup::class],
    'empty state' => ['empty-state', EmptyState::class],
    'progress' => ['progress', Progress::class],
    'table' => ['table', Table::class],
    'tabs' => ['tabs', Tabs::class],
]);

it('projects Toggle Group slots from one family declaration', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(ToggleGroup::SLOTS)->toBe([
        'root' => ['name' => 'toggle-group', 'kind' => 'visual'],
        'item' => ['name' => 'toggle-group-item', 'kind' => 'visual'],
    ])->and($catalog['components']['toggle-group']['styling']['slots'])->toBe([
        ['class' => ToggleGroup::class],
    ])->and($catalog['components']['toggle-group.item']['styling']['slots'])->toBe([
        ['class' => ToggleGroup::class, 'only' => ['item']],
    ])->and(HotwireRegistry::make()->component('toggle-group')->styling->slots)->toBe([
        'toggle-group' => 'visual',
        'toggle-group-item' => 'visual',
    ])->and(HotwireRegistry::make()->component('toggle-group.item')->styling->slots)->toBe([
        'toggle-group-item' => 'visual',
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

it('projects the Drawer family slot contract from its component class', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(Drawer::SLOTS)->toBe([
        'overlay' => ['name' => 'drawer-overlay', 'kind' => 'visual'],
        'trigger' => ['name' => 'drawer-trigger', 'kind' => 'visual'],
        'backdrop' => ['name' => 'drawer-backdrop', 'kind' => 'visual'],
        'popup' => ['name' => 'drawer-popup', 'kind' => 'visual'],
        'content' => ['name' => 'drawer-content', 'kind' => 'visual'],
        'header' => ['name' => 'drawer-header', 'kind' => 'visual'],
        'title' => ['name' => 'drawer-title', 'kind' => 'visual'],
        'description' => ['name' => 'drawer-description', 'kind' => 'visual'],
        'footer' => ['name' => 'drawer-footer', 'kind' => 'visual'],
        'close' => ['name' => 'drawer-close', 'kind' => 'visual'],
        'root' => ['name' => 'drawer', 'kind' => 'structural'],
    ])->and($catalog['components']['drawer']['styling']['slots'])->toBe([
        ['class' => Drawer::class],
    ])->and(HotwireRegistry::make()->component('drawer')->styling->slots)->toBe([
        'drawer-overlay' => 'visual',
        'drawer-trigger' => 'visual',
        'drawer-backdrop' => 'visual',
        'drawer-popup' => 'visual',
        'drawer-content' => 'visual',
        'drawer-header' => 'visual',
        'drawer-title' => 'visual',
        'drawer-description' => 'visual',
        'drawer-footer' => 'visual',
        'drawer-close' => 'visual',
        'drawer' => 'structural',
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

it('projects the Modal family slot contract from its component class', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(Modal::SLOTS)->toBe([
        'overlay' => ['name' => 'modal-overlay', 'kind' => 'visual'],
        'trigger' => ['name' => 'modal-trigger', 'kind' => 'visual'],
        'backdrop' => ['name' => 'modal-backdrop', 'kind' => 'visual'],
        'positioner' => ['name' => 'modal-positioner', 'kind' => 'visual'],
        'panel' => ['name' => 'modal-panel', 'kind' => 'visual'],
        'content' => ['name' => 'modal-content', 'kind' => 'visual'],
        'header' => ['name' => 'modal-header', 'kind' => 'visual'],
        'title' => ['name' => 'modal-title', 'kind' => 'visual'],
        'description' => ['name' => 'modal-description', 'kind' => 'visual'],
        'footer' => ['name' => 'modal-footer', 'kind' => 'visual'],
        'close' => ['name' => 'modal-close', 'kind' => 'visual'],
        'close-icon' => ['name' => 'modal-close-icon', 'kind' => 'visual'],
        'root' => ['name' => 'modal', 'kind' => 'structural'],
    ])->and($catalog['components']['modal']['styling']['slots'])->toBe([
        ['class' => Modal::class],
    ])->and(HotwireRegistry::make()->component('modal')->styling->slots)->toBe([
        'modal-overlay' => 'visual',
        'modal-trigger' => 'visual',
        'modal-backdrop' => 'visual',
        'modal-positioner' => 'visual',
        'modal-panel' => 'visual',
        'modal-content' => 'visual',
        'modal-header' => 'visual',
        'modal-title' => 'visual',
        'modal-description' => 'visual',
        'modal-footer' => 'visual',
        'modal-close' => 'visual',
        'modal-close-icon' => 'visual',
        'modal' => 'structural',
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

it('projects the Sheet family slot contract from its component class', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    expect(Sheet::SLOTS)->toBe([
        'overlay' => ['name' => 'sheet-overlay', 'kind' => 'visual'],
        'trigger' => ['name' => 'sheet-trigger', 'kind' => 'visual'],
        'backdrop' => ['name' => 'sheet-backdrop', 'kind' => 'visual'],
        'content' => ['name' => 'sheet-content', 'kind' => 'visual'],
        'header' => ['name' => 'sheet-header', 'kind' => 'visual'],
        'title' => ['name' => 'sheet-title', 'kind' => 'visual'],
        'description' => ['name' => 'sheet-description', 'kind' => 'visual'],
        'footer' => ['name' => 'sheet-footer', 'kind' => 'visual'],
        'close' => ['name' => 'sheet-close', 'kind' => 'visual'],
        'close-icon' => ['name' => 'sheet-close-icon', 'kind' => 'visual'],
        'root' => ['name' => 'sheet', 'kind' => 'structural'],
    ])->and($catalog['components']['sheet']['styling']['slots'])->toBe([
        ['class' => Sheet::class],
    ])->and(HotwireRegistry::make()->component('sheet')->styling->slots)->toBe([
        'sheet-overlay' => 'visual',
        'sheet-trigger' => 'visual',
        'sheet-backdrop' => 'visual',
        'sheet-content' => 'visual',
        'sheet-header' => 'visual',
        'sheet-title' => 'visual',
        'sheet-description' => 'visual',
        'sheet-footer' => 'visual',
        'sheet-close' => 'visual',
        'sheet-close-icon' => 'visual',
        'sheet' => 'structural',
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
