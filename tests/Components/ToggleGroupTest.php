<?php

use Emaia\LaravelHotwire\Components\ToggleGroup;
use Emaia\LaravelHotwire\Components\ToggleGroup\Item as ToggleGroupItem;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

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
