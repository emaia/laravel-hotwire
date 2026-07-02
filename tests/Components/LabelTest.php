<?php

use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
});

// --- for attribute ---

it('uses explicit for attribute', function () {
    $view = $this->blade('<x-hwc::field.label for="email">E-mail</x-hwc::field.label>');

    $view->assertSee('for="email"', false);
    $view->assertSee('E-mail');
});

it('derives for from name prop', function () {
    $view = $this->blade('<x-hwc::field.label name="email">E-mail</x-hwc::field.label>');

    $view->assertSee('for="email"', false);
});

it('derives for from name with bracket notation', function () {
    $view = $this->blade('<x-hwc::field.label name="variables[0][name]">Variables</x-hwc::field.label>');

    $view->assertSee('for="variables-0-name"', false);
});

it('does not render for attribute without name or for', function () {
    $view = $this->blade('<x-hwc::field.label>Generic</x-hwc::field.label>');

    $view->assertDontSee('for=', false);
    $view->assertSee('Generic');
});

// --- Implicit-label wrap (label contains the control) ---

it('omits for when the slot wraps an input (implicit labeling)', function () {
    $view = $this->blade('<x-hwc::field.label name="switch"><input type="checkbox" />Airplane</x-hwc::field.label>');

    $view->assertDontSee('for=', false);
});

it('omits for when the slot wraps an x-hwc::input', function () {
    $view = $this->blade('<x-hwc::field.label name="switch"><x-hwc::input type="checkbox" name="switch" />Airplane</x-hwc::field.label>');

    $view->assertDontSee('for=', false);
});

it('omits for when the slot wraps a select', function () {
    $view = $this->blade('<x-hwc::field.label name="plan"><select><option>A</option></select> Plan</x-hwc::field.label>');

    $view->assertDontSee('for=', false);
});

it('omits for when the slot wraps a textarea', function () {
    $view = $this->blade('<x-hwc::field.label name="bio"><textarea></textarea> Bio</x-hwc::field.label>');

    $view->assertDontSee('for=', false);
});

it('still emits for when slot has plain text (no wrapped control)', function () {
    $view = $this->blade('<x-hwc::field.label name="email">E-mail</x-hwc::field.label>');

    $view->assertSee('for="email"', false);
});

it('respects explicit for even when slot wraps a control', function () {
    $view = $this->blade('<x-hwc::field.label for="custom"><input type="text" />Inner</x-hwc::field.label>');

    $view->assertSee('for="custom"', false);
});

// --- Content via value vs slot ---

it('uses value prop as content', function () {
    $view = $this->blade('<x-hwc::field.label for="email" value="My Label" />');

    $view->assertSee('My Label');
});

it('prefers slot over value when both provided', function () {
    $view = $this->blade('<x-hwc::field.label for="email" value="From prop">From slot</x-hwc::field.label>');

    $view->assertSee('From slot');
    $view->assertDontSee('From prop');
});

// --- Required marker ---

it('renders required marker when required', function () {
    $view = $this->blade('<x-hwc::field.label for="email" required>E-mail</x-hwc::field.label>');

    $view->assertSee('required', false);
    $view->assertSee('*');
    $view->assertSee('aria-hidden="true"', false);
});

it('uses custom required label', function () {
    $view = $this->blade('<x-hwc::field.label for="email" required required-label="(obrigatório)">E-mail</x-hwc::field.label>');

    $view->assertSee('(obrigatório)');
});

it('does not render required marker by default', function () {
    $view = $this->blade('<x-hwc::field.label for="email">E-mail</x-hwc::field.label>');

    $view->assertDontSee('required', false);
});

// --- Class merge ---

it('merges custom class on the label element', function () {
    $view = $this->blade('<x-hwc::field.label for="email" class="text-sm font-bold">E-mail</x-hwc::field.label>');

    $view->assertSee('data-slot="field-label"', false);
    $view->assertSee('class="text-sm font-bold"', false);
});

it('does not render an empty class attribute', function () {
    $view = $this->blade('<x-hwc::field.label for="email">E-mail</x-hwc::field.label>');

    $view->assertSee('data-slot="field-label"', false);
    $view->assertDontSee('class=""', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hwc::field.label for="email" data-test="x">E-mail</x-hwc::field.label>');

    $view->assertSee('data-test="x"', false);
});

// --- @aware propagation from field ---

it('picks up name and derives for from field via @aware', function () {
    $view = $this->blade('
        <x-hwc::field name="email">
            <x-hwc::field.label>E-mail</x-hwc::field.label>
        </x-hwc::field>
    ');

    $view->assertSee('for="email"', false);
    $view->assertSee('E-mail');
});

it('picks up required from field via @aware', function () {
    $view = $this->blade('
        <x-hwc::field name="email" required>
            <x-hwc::field.label>E-mail</x-hwc::field.label>
        </x-hwc::field>
    ');

    $view->assertSee('*');
    $view->assertSee('aria-hidden="true"', false);
});
