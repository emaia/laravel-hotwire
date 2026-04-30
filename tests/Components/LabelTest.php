<?php

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

// --- Optional marker ---

it('renders optional marker when optional', function () {
    $view = $this->blade('<x-hwc::label for="email" optional>E-mail</x-hwc::label>');

    $view->assertSee('optional', false);
    $view->assertSee('(opcional)');
});

it('does not render optional when required is set', function () {
    $view = $this->blade('<x-hwc::label for="email" required optional>E-mail</x-hwc::label>');

    $view->assertSee('required', false);
    $view->assertDontSee('optional', false);
});

// --- Info tooltip ---

it('renders info tooltip with tippy controller', function () {
    $view = $this->blade('<x-hwc::label for="email" info="Helpful tip">E-mail</x-hwc::label>');

    $view->assertSee('data-controller="tooltip"', false);
    $view->assertSee('data-tooltip-content-value="Helpful tip"', false);
});

it('does not render info when not provided', function () {
    $view = $this->blade('<x-hwc::label for="email">E-mail</x-hwc::label>');

    $view->assertDontSee('data-controller="tooltip"', false);
});

// --- Class merge ---

it('merges custom class with label', function () {
    $view = $this->blade('<x-hwc::label for="email" class="text-sm font-bold">E-mail</x-hwc::label>');

    $view->assertSee('label', false);
    $view->assertSee('text-sm font-bold', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hwc::label for="email" data-test="x">E-mail</x-hwc::label>');

    $view->assertSee('data-test="x"', false);
});
