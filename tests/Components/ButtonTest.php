<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;

// --- Rendering basics ---

it('renders a native <button> element with the default type', function () {
    $view = $this->blade('<x-hw::button>Save</x-hw::button>');

    $view->assertSee('<button', false)
        ->assertSee('type="button"', false)
        ->assertSee('</button>', false);
    $view->assertSeeText('Save');
});

it('honours the type prop', function () {
    $view = $this->blade('<x-hw::button type="submit">Save</x-hw::button>');

    $view->assertSee('type="submit"', false);
});

it('emits the data-slot/variant/size attributes on every render', function () {
    $view = $this->blade('<x-hw::button variant="destructive" size="lg">Delete</x-hw::button>');

    $view->assertSee('data-slot="button"', false)
        ->assertSee('data-variant="destructive"', false)
        ->assertSee('data-size="lg"', false);
});

// --- Semantic styling contract ---

it('does not emit package Tailwind classes inline', function () {
    $view = $this->blade('<x-hw::button>Save</x-hw::button>');

    $view->assertDontSee('bg-primary', false)
        ->assertDontSee('inline-flex', false)
        ->assertDontSee('focus-visible:ring', false);
});

// --- Pass-through ---

it('passes through a user class without adding package classes', function () {
    $view = $this->blade('<x-hw::button class="w-full">Save</x-hw::button>');

    $view->assertSee('class="w-full"', false)
        ->assertDontSee('bg-primary', false);
});

it('passes through arbitrary HTML attributes', function () {
    $view = $this->blade('<x-hw::button id="save" name="action" disabled aria-label="Save form" data-test="primary">Save</x-hw::button>');

    $view->assertSee('id="save"', false)
        ->assertSee('name="action"', false)
        ->assertSee('disabled', false)
        ->assertSee('aria-label="Save form"', false)
        ->assertSee('data-test="primary"', false);
});

// --- `as` prop (render as a different tag) ---

it('renders as <a> when as="a" is passed and omits the type attribute', function () {
    $view = $this->blade('<x-hw::button as="a" href="/dashboard">Dashboard</x-hw::button>');

    $view->assertSee('<a', false)
        ->assertSee('href="/dashboard"', false)
        ->assertSee('</a>', false)
        ->assertDontSee('<button', false)
        ->assertDontSee('type="', false);
});

it('keeps the data-slot/variant/size attributes on the <a> render', function () {
    $view = $this->blade('<x-hw::button as="a" variant="link" href="/x">Link</x-hw::button>');

    $view->assertSee('data-slot="button"', false)
        ->assertSee('data-variant="link"', false)
        ->assertSee('data-size="default"', false);
});

it('renders frame as a Turbo Frame target', function () {
    $view = $this->blade('<x-hw::button as="a" href="/tasks/create" frame="modal">New task</x-hw::button>');

    $view->assertSee('data-turbo-frame="modal"', false)
        ->assertDontSee(' frame="modal"', false);
});

it('lets an explicit data-turbo-frame attribute override the frame prop', function () {
    $view = $this->blade('<x-hw::button as="a" href="/tasks/create" frame="modal" data-turbo-frame="drawer">New task</x-hw::button>');

    $view->assertSee('data-turbo-frame="drawer"', false)
        ->assertDontSee('data-turbo-frame="modal"', false)
        ->assertDontSee(' frame="modal"', false);
});

// --- Declarative controller integrations ---

it('renders a hotkey controller and action', function () {
    $view = $this->blade('<x-hw::button hotkey="ctrl+s">Save</x-hw::button>');

    $view->assertSee('data-controller="hotkey"', false)
        ->assertSee('data-action="keydown.ctrl+s@window->hotkey#click"', false)
        ->assertDontSee(' hotkey="ctrl+s"', false);
});

it('normalizes cmd hotkey aliases and supports multiple shortcuts', function () {
    $view = $this->blade('<x-hw::button hotkey="cmd+s ctrl+s">Save</x-hw::button>');

    $view->assertSee('data-action="keydown.meta+s@window->hotkey#click keydown.ctrl+s@window->hotkey#click"', false)
        ->assertDontSee('keydown.cmd+s', false);
});

it('merges hotkey actions with raw and fluent stimulus attributes', function () {
    $view = $this->blade('<x-hw::button hotkey="cmd+s" data-controller="analytics" data-action="click->analytics#track" :stimulus="stimulus()->controller(\'hotkey\')->action(\'hotkey\', \'click\', \'keydown.meta+s@window\')">Save</x-hw::button>');

    $html = (string) $view;
    expect($html)->toContain('data-controller="hotkey analytics"')
        ->and($html)->toContain('data-action="keydown.meta+s@window->hotkey#click click->analytics#track"')
        ->and(substr_count($html, 'keydown.meta+s@window->hotkey#click'))->toBe(1);
});

it('renders tooltip values from props', function () {
    $view = $this->blade('<x-hw::button tooltip="Save changes" tooltip-side="bottom" tooltip-align="end" tooltip-enabled-when="[data-ready=true]">Save</x-hw::button>');

    $view->assertSee('data-controller="tooltip"', false)
        ->assertSee('data-tooltip-content-value="Save changes"', false)
        ->assertSee('data-tooltip-side-value="bottom"', false)
        ->assertSee('data-tooltip-align-value="end"', false)
        ->assertSee('data-tooltip-enabled-when-value="[data-ready=true]"', false)
        ->assertDontSee(' tooltip="Save changes"', false)
        ->assertDontSee(' tooltip-side="bottom"', false)
        ->assertDontSee(' tooltip-align="end"', false)
        ->assertDontSee(' tooltip-enabled-when="[data-ready=true]"', false);
});

it('lets tooltip props own data-tooltip values when tooltip is active', function () {
    $view = $this->blade('<x-hw::button tooltip="Save changes" tooltip-side="bottom" tooltip-align="end" data-tooltip-content-value="Override" data-tooltip-side-value="right" data-tooltip-align-value="start">Save</x-hw::button>');

    $view->assertSee('data-tooltip-content-value="Save changes"', false)
        ->assertSee('data-tooltip-side-value="bottom"', false)
        ->assertSee('data-tooltip-align-value="end"', false)
        ->assertDontSee('data-tooltip-content-value="Override"', false)
        ->assertDontSee('data-tooltip-side-value="right"', false)
        ->assertDontSee('data-tooltip-align-value="start"', false);
});

it('merges hotkey and tooltip controllers together', function () {
    $view = $this->blade('<x-hw::button hotkey="ctrl+s" tooltip="Save changes">Save</x-hw::button>');

    $view->assertSee('data-controller="hotkey tooltip"', false)
        ->assertSee('data-action="keydown.ctrl+s@window->hotkey#click"', false)
        ->assertSee('data-tooltip-content-value="Save changes"', false);
});

it('uses semantic variant attributes regardless of the rendered tag', function () {
    $view = $this->blade('<x-hw::button as="a" variant="destructive" href="/x">Delete</x-hw::button>');

    $view->assertSee('data-variant="destructive"', false)
        ->assertDontSee('bg-destructive', false);
});

// --- `stimulus` prop (Hotwire-stack integration) ---

it('renders inline stimulus attributes when :stimulus is passed', function () {
    $view = $this->blade('<x-hw::button as="a" href="/x" :stimulus="stimulus()->controller(\'hotkey\')->action(\'hotkey\', \'click\', \'keydown.n@window\')">New Task</x-hw::button>');

    $view->assertSee('<a', false)
        ->assertSee('data-controller="hotkey"', false)
        ->assertSee('data-action=', false);
});

it('merges raw stimulus attributes with the stimulus prop', function () {
    $view = $this->blade('<x-hw::button data-controller="analytics" data-action="click->analytics#track" :stimulus="stimulus()->controller(\'hotkey\')->action(\'hotkey\', \'click\', \'keydown.n@window\')">New Task</x-hw::button>');

    $view->assertSee('data-controller="analytics hotkey"', false)
        ->assertSee('data-action="click->analytics#track keydown.n@window->hotkey#click"', false);
});

it('omits stimulus attributes when no :stimulus prop is passed', function () {
    $view = $this->blade('<x-hw::button>Save</x-hw::button>');

    $view->assertDontSee('data-controller', false)
        ->assertDontSee('data-action', false);
});

// --- Catalog ---

it('registers optional button controllers in the catalog', function () {
    $button = HotwireRegistry::make()->component('button');

    expect($button->controllers)->toBe(['hotkey', 'tooltip']);
});
