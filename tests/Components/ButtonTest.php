<?php

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

it('uses semantic variant attributes regardless of the rendered tag', function () {
    $view = $this->blade('<x-hw::button as="a" variant="destructive" href="/x">Delete</x-hw::button>');

    $view->assertSee('data-variant="destructive"', false)
        ->assertDontSee('bg-destructive', false);
});

// --- `stimulus` prop (Hotwire-stack integration) ---

it('renders inline stimulus attributes when :stimulus is passed', function () {
    // Blade's component parser does not support `{{ $stimulusBag }}` as a bare
    // attribute spread (regardless of expression complexity), so the component
    // exposes a named :stimulus prop that accepts an Htmlable and emits its
    // toHtml() raw alongside the regular attribute bag.
    $view = $this->blade('<x-hw::button as="a" href="/x" :stimulus="stimulus()->controller(\'hotkey\')->action(\'hotkey\', \'click\', \'keydown.n@window\')">New Task</x-hw::button>');

    $view->assertSee('<a', false)
        ->assertSee('data-controller="hotkey"', false)
        ->assertSee('data-action=', false);
});

it('omits stimulus attributes when no :stimulus prop is passed', function () {
    $view = $this->blade('<x-hw::button>Save</x-hw::button>');

    $view->assertDontSee('data-controller', false)
        ->assertDontSee('data-action', false);
});
