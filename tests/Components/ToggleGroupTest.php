<?php

use Emaia\LaravelHotwire\Components\ToggleGroup;
use Emaia\LaravelHotwire\Components\ToggleGroup\Item as ToggleGroupItem;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ViewException;

function shareToggleGroupErrors(array $errorsByKey): void
{
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag($errorsByKey));
    view()->share('errors', $bag);
}

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
    request()->setLaravelSession($this->app['session.store']);
    session()->forget('_old_input');
});

// --- Basic render ---

it('renders a single-selection toggle group with button items and hidden inputs', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::toggle-group type="single" name="alignment" value="left" variant="outline" size="sm" aria-label="Text alignment">
            <x-hw::toggle-group.item value="left">Left</x-hw::toggle-group.item>
            <x-hw::toggle-group.item value="center">Center</x-hw::toggle-group.item>
        </x-hw::toggle-group>
    BLADE);

    expect($html)->toContain('role="group"')
        ->and($html)->toContain('aria-label="Text alignment"')
        ->and($html)->toContain('data-slot="toggle-group"')
        ->and($html)->toContain('data-controller="toggle-group"')
        ->and($html)->toContain('data-action="change->toggle-group#sync"')
        ->and($html)->toContain('data-toggle-group-type-value="single"')
        ->and($html)->toContain('data-orientation="horizontal"')
        ->and($html)->toContain('data-variant="outline"')
        ->and($html)->toContain('data-size="sm"')
        ->and($html)->toContain('data-slot="toggle-group-item"')
        ->and($html)->toContain('data-controller="toggle"')
        ->and($html)->toContain('data-action="click->toggle#toggle"')
        ->and($html)->toContain('data-toggle-group-target="item"')
        ->and($html)->toContain('name="alignment"')
        ->and($html)->toContain('value="left"')
        ->and(substr_count($html, 'type="hidden"'))->toBe(2)
        ->and(substr_count($html, 'data-toggle-pressed-value="true"'))->toBe(1)
        ->and(substr_count($html, 'data-toggle-pressed-value="false"'))->toBe(1);
});

it('renders a multiple-selection group and normalizes the form name to an array', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::toggle-group type="multiple" name="formats" :value="['bold', 'italic']" orientation="vertical" connected>
            <x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item>
            <x-hw::toggle-group.item value="italic">Italic</x-hw::toggle-group.item>
            <x-hw::toggle-group.item value="underline">Underline</x-hw::toggle-group.item>
        </x-hw::toggle-group>
    BLADE);

    expect($html)->toContain('data-toggle-group-type-value="multiple"')
        ->and($html)->toContain('data-orientation="vertical"')
        ->and($html)->toContain('data-connected="true"')
        ->and(substr_count($html, 'name="formats[]"'))->toBe(3)
        ->and(substr_count($html, 'data-toggle-pressed-value="true"'))->toBe(2)
        ->and(substr_count($html, 'data-toggle-pressed-value="false"'))->toBe(1);
});

it('inherits name from a field wrapper and keeps single names scalar', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::field name="alignment">
            <x-hw::toggle-group type="single" value="left">
                <x-hw::toggle-group.item value="left">Left</x-hw::toggle-group.item>
            </x-hw::toggle-group>
        </x-hw::field>
    BLADE);

    $view->assertSee('name="alignment"', false)
        ->assertSee('id="alignment-left-input"', false)
        ->assertDontSee('name="alignment[]"', false);
});

// --- Value + old() ---

it('publishes only toggle-group-scoped component data', function () {
    $groupData = (new ToggleGroup(name: 'formats', value: ['bold'], disabled: true))->data();
    $itemData = (new ToggleGroupItem(value: 'bold', name: 'item-formats', disabled: false))->data();
    $frameworkKeys = ['componentName', 'attributes', 'ignoredParameterNames', 'internalPrefixes', 'compute'];

    $groupGenericKeys = array_values(array_filter(
        array_keys($groupData),
        fn (string $key) => ! str_starts_with($key, 'toggleGroup') && ! str_starts_with($key, 'fieldOwner') && ! in_array($key, $frameworkKeys, true),
    ));
    $itemGenericKeys = array_values(array_filter(
        array_keys($itemData),
        fn (string $key) => ! str_starts_with($key, 'toggleGroupItem') && ! in_array($key, $frameworkKeys, true),
    ));

    expect($groupGenericKeys)->toBe([])
        ->and($itemGenericKeys)->toBe([])
        ->and($groupData)->toHaveKeys(['toggleGroupContext', 'toggleGroupName', 'toggleGroupSelected', 'toggleGroupDisabled'])
        ->and($groupData)->not->toHaveKey('groupDisabled')
        ->and($itemData)->toHaveKeys(['toggleGroupItemValue', 'toggleGroupItemName', 'toggleGroupItemDisabled']);
});

it('publishes a null field owner boundary when the toggle group is nameless', function () {
    $data = (new ToggleGroup)->data();

    expect($data)->toHaveKey('fieldOwner', false)
        ->and($data)->toHaveKey('fieldOwnerName', null)
        ->and($data)->toHaveKey('fieldOwnerId', null)
        ->and($data)->toHaveKey('fieldOwnerErrorKey', null);
});

it('keeps toggle group context through an intermediate component', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $view = $this->blade(<<<'BLADE'
        <x-hw::toggle-group name="formats" id="owner-toggle" type="multiple" :value="['bold']" variant="default" size="sm">
            <x-selection-context-wrapper name="shadow-group" id="shadow-group-id" error-key="shadow.group" :selected="[]" :old="false" disabled :select-all="false" type="single" variant="outline" size="lg" group-disabled :auto-submit="false" :auto-submit-delay="1">
                <x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item>
            </x-selection-context-wrapper>
        </x-hw::toggle-group>
    BLADE);

    $view->assertSee('name="formats[]"', false)
        ->assertSee('id="owner-toggle-bold-input"', false)
        ->assertSee('data-variant="default"', false)
        ->assertSee('data-size="sm"', false)
        ->assertSee('data-toggle-pressed-value="true"', false)
        ->assertDontSee('shadow-group', false)
        ->assertDontSee(' disabled', false);
});

it('keeps toggle items bound to their family across another selection group', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::toggle-group name="formats" :value="['bold']">
            <x-hw::radio-group name="plan" selected="pro">
                <x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item>
            </x-hw::radio-group>
        </x-hw::toggle-group>
    BLADE);

    $view->assertSee('name="formats[]"', false)
        ->assertSee('id="formats-bold-input"', false)
        ->assertSee('data-toggle-pressed-value="true"', false);
});

it('binds toggle items to the nearest same-family root without leaking nullable context', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::toggle-group name="outer-formats" value="outer">
            <x-hw::toggle-group value="inner">
                <x-hw::toggle-group.item value="inner">Inner</x-hw::toggle-group.item>
            </x-hw::toggle-group>
        </x-hw::toggle-group>
    BLADE);

    $view->assertSee('data-toggle-pressed-value="true"', false)
        ->assertDontSee('name="outer-formats[]"', false)
        ->assertDontSee('id="outer-formats-inner-input"', false);
});

it('keeps explicit toggle item identity ahead of group and field context', function () {
    shareToggleGroupErrors(['item.formats' => ['Choose a format.']]);

    $view = $this->blade(<<<'BLADE'
        <x-hw::field name="field-formats" id="field-toggle" error-key="field.formats">
            <x-hw::toggle-group name="group-formats" id="group-toggle" error-key="group.formats">
                <x-hw::toggle-group.item value="bold" name="item-formats" id="item-toggle" error-key="item.formats">Bold</x-hw::toggle-group.item>
            </x-hw::toggle-group>
        </x-hw::field>
    BLADE);

    $view->assertSee('name="item-formats[]"', false)
        ->assertSee('id="item-toggle-bold-input"', false)
        ->assertSee('aria-describedby="item-toggle-error"', false)
        ->assertSee('aria-invalid="true"', false);
});

it('requires toggle group items to render inside a toggle group root', function () {
    $this->blade('<x-hw::toggle-group.item value="bold" name="formats">Bold</x-hw::toggle-group.item>');
})->throws(ViewException::class, 'must be rendered inside a Toggle Group root');

it('restores selected values from old input', function () {
    session()->put('_old_input', ['formats' => ['italic']]);

    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::toggle-group type="multiple" name="formats" :value="['bold']">
            <x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item>
            <x-hw::toggle-group.item value="italic">Italic</x-hw::toggle-group.item>
        </x-hw::toggle-group>
    BLADE);

    expect(substr_count($html, 'data-toggle-pressed-value="true"'))->toBe(1)
        ->and($html)->toContain('data-toggle-value-value="italic"');
});

it('can opt out of old input restoration', function () {
    session()->put('_old_input', ['formats' => ['italic']]);

    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::toggle-group type="multiple" name="formats" :value="['bold']" :old="false">
            <x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item>
            <x-hw::toggle-group.item value="italic">Italic</x-hw::toggle-group.item>
        </x-hw::toggle-group>
    BLADE);

    expect(substr_count($html, 'data-toggle-pressed-value="true"'))->toBe(1)
        ->and($html)->toContain('data-toggle-value-value="bold"');
});

// --- State and attributes ---

it('propagates disabled state from the group to each item', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::toggle-group type="multiple" name="formats" :value="['bold']" disabled>
            <x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item>
            <x-hw::toggle-group.item value="italic">Italic</x-hw::toggle-group.item>
        </x-hw::toggle-group>
    BLADE);

    expect($html)->toContain('aria-disabled="true"')
        ->and($html)->toContain('data-disabled="true"')
        ->and(substr_count($html, 'disabled'))->toBeGreaterThanOrEqual(2);
});

it('sets validation state from the group error key on items', function () {
    shareToggleGroupErrors(['formats' => ['Choose a format.']]);

    $view = $this->blade(<<<'BLADE'
        <x-hw::toggle-group type="multiple" name="formats">
            <x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item>
        </x-hw::toggle-group>
    BLADE);

    $view->assertSee('aria-describedby="formats-error"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('data-invalid', false);
});

it('does not emit package Tailwind classes inline', function () {
    $view = $this->blade('<x-hw::toggle-group><x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item></x-hw::toggle-group>');

    $view->assertDontSee('inline-flex', false)
        ->assertDontSee('bg-primary', false)
        ->assertDontSee('focus-visible:ring', false);
});

// --- Stimulus merge ---

it('merges user stimulus attributes on the group', function () {
    $view = $this->blade('<x-hw::toggle-group data-controller="analytics" data-action="change->analytics#track"><x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item></x-hw::toggle-group>');

    $view->assertSee('data-controller="toggle-group analytics"', false)
        ->assertSee('data-action="change->toggle-group#sync change->analytics#track"', false);
});

it('merges user stimulus attributes on items and protects internal toggle values', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::toggle-group value="bold">
            <x-hw::toggle-group.item value="bold" data-controller="analytics" data-action="click->analytics#track" data-toggle-pressed-value="false">Bold</x-hw::toggle-group.item>
        </x-hw::toggle-group>
    BLADE);

    $view->assertSee('data-controller="toggle analytics"', false)
        ->assertSee('data-action="click->toggle#toggle click->analytics#track"', false)
        ->assertSee('data-toggle-pressed-value="true"', false)
        ->assertDontSee('data-toggle-pressed-value="false"', false);
});

it('can opt into auto-submit on group changes', function () {
    $view = $this->blade('<x-hw::toggle-group auto-submit><x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item></x-hw::toggle-group>');

    $view->assertSee('data-controller="toggle-group"', false)
        ->assertDontSee('data-controller="toggle-group auto-submit"', false)
        ->assertSee('data-action="change->toggle-group#sync change->auto-submit#submit"', false);
});

// --- Catalog ---

it('registers toggle group in the component and controller catalogs', function () {
    $registry = HotwireRegistry::make();
    $group = $registry->component('toggle-group');
    $item = $registry->component('toggle-group.item');
    $controller = $registry->controller('toggle-group');

    expect($group->class)->toBe(ToggleGroup::class)
        ->and($group->view)->toBe('hotwire::component-views.toggle-group')
        ->and($group->controllers)->toBe(['toggle-group', 'toggle', 'auto-submit'])
        ->and($item->class)->toBe(ToggleGroupItem::class)
        ->and($controller->source)->toBe('resources/js/controllers/toggle_group_controller.js')
        ->and($controller->docs)->toBe('docs/controllers/toggle-group.md')
        ->and(ComponentAliases::subComponents())->toHaveKey('toggle-group.item', ToggleGroupItem::class);
});

it('resolves field error and label against the toggle group name without a field root', function () {
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag(['formats' => ['Escolha uma opcao']]));
    view()->share('errors', $bag);

    $view = $this->blade(<<<'BLADE'
        <x-hw::toggle-group name="formats">
            <x-hw::field.label>Rotulo</x-hw::field.label>
            <x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item>
            <x-hw::field.error />
        </x-hw::toggle-group>
    BLADE);

    $html = (string) $view;

    expect($html)->toContain('id="formats-error"')
        ->toContain('Escolha uma opcao')
        ->toContain('aria-labelledby="formats-label"')
        ->toContain('id="formats-label"')
        ->not->toContain('hw-error-');
});

it('points a slot-hoisted toggle item at the wrapper slot as the likely cause', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $this->blade('<x-selection-slot-wrapper><x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item></x-selection-slot-wrapper>');
})->throws(ViewException::class, 'slot content renders before the view of the wrapper');

it('names a toggle group with aria-labelledby instead of a dangling label for', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::toggle-group name="formats">
            <x-hw::field.label>Formats</x-hw::field.label>
            <x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item>
        </x-hw::toggle-group>
    BLADE);

    $view->assertSee('role="group"', false)
        ->assertSee('aria-labelledby="formats-label"', false)
        ->assertSee('id="formats-label"', false)
        ->assertDontSee('for="formats"', false);
});

it('names a toggle group from a surrounding field label', function () {
    $html = (string) $this->blade('<x-hw::field name="formats" label="Formats" :error="false"><x-hw::toggle-group><x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item></x-hw::toggle-group></x-hw::field>');

    expect($html)->toMatch('/<div(?=[^>]*data-slot="toggle-group")(?=[^>]*aria-labelledby="formats-label")[^>]*>/')
        ->toContain('id="formats-label"')
        ->not->toContain('for="formats"');
});
