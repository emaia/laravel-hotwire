<?php

use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
});

// --- for attribute ---

it('uses explicit for attribute', function () {
    $view = $this->blade('<x-hwc::label for="email">E-mail</x-hwc::label>');

    $view->assertSee('for="email"', false);
    $view->assertSee('E-mail');
});

it('derives for from name prop', function () {
    $view = $this->blade('<x-hwc::label name="email">E-mail</x-hwc::label>');

    $view->assertSee('for="email"', false);
});

it('derives for from name with bracket notation', function () {
    $view = $this->blade('<x-hwc::label name="variables[0][name]">Variables</x-hwc::label>');

    $view->assertSee('for="variables-0-name"', false);
});

it('does not render for attribute without name or for', function () {
    $view = $this->blade('<x-hwc::label>Generic</x-hwc::label>');

    $view->assertDontSee('for=', false);
    $view->assertSee('Generic');
});

// --- Implicit-label wrap (label contains the control) ---

it('omits for when the slot wraps an input (implicit labeling)', function () {
    $view = $this->blade('<x-hwc::label name="switch"><input type="checkbox" />Airplane</x-hwc::label>');

    $view->assertDontSee('for=', false);
});

it('omits for when the slot wraps an x-hwc::input', function () {
    $view = $this->blade('<x-hwc::label name="switch"><x-hwc::input type="checkbox" name="switch" />Airplane</x-hwc::label>');

    $view->assertDontSee('for=', false);
});

it('omits for when the slot wraps a select', function () {
    $view = $this->blade('<x-hwc::label name="plan"><select><option>A</option></select> Plan</x-hwc::label>');

    $view->assertDontSee('for=', false);
});

it('omits for when the slot wraps a textarea', function () {
    $view = $this->blade('<x-hwc::label name="bio"><textarea></textarea> Bio</x-hwc::label>');

    $view->assertDontSee('for=', false);
});

it('still emits for when slot has plain text (no wrapped control)', function () {
    $view = $this->blade('<x-hwc::label name="email">E-mail</x-hwc::label>');

    $view->assertSee('for="email"', false);
});

it('respects explicit for even when slot wraps a control', function () {
    $view = $this->blade('<x-hwc::label for="custom"><input type="text" />Inner</x-hwc::label>');

    $view->assertSee('for="custom"', false);
});

// --- Content via value vs slot ---

it('uses value prop as content', function () {
    $view = $this->blade('<x-hwc::label for="email" value="My Label" />');

    $view->assertSee('My Label');
});

it('prefers slot over value when both provided', function () {
    $view = $this->blade('<x-hwc::label for="email" value="From prop">From slot</x-hwc::label>');

    $view->assertSee('From slot');
    $view->assertDontSee('From prop');
});

// --- Required marker ---

it('renders required marker when required', function () {
    $view = $this->blade('<x-hwc::label for="email" required>E-mail</x-hwc::label>');

    $view->assertSee('required', false);
    $view->assertSee('*');
    $view->assertSee('aria-hidden="true"', false);
});

it('uses custom required label', function () {
    $view = $this->blade('<x-hwc::label for="email" required required-label="(obrigatório)">E-mail</x-hwc::label>');

    $view->assertSee('(obrigatório)');
});

it('does not render required marker by default', function () {
    $view = $this->blade('<x-hwc::label for="email">E-mail</x-hwc::label>');

    $view->assertDontSee('required', false);
});

// --- Class merge ---

it('merges custom class with hwc-label', function () {
    $view = $this->blade('<x-hwc::label for="email" class="text-sm font-bold">E-mail</x-hwc::label>');

    $view->assertSee('hwc-label', false);
    $view->assertSee('text-sm font-bold', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hwc::label for="email" data-test="x">E-mail</x-hwc::label>');

    $view->assertSee('data-test="x"', false);
});
