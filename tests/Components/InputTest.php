<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function shareInputErrors(array $errorsByKey): void
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

// --- Plain render ---

it('renders a plain input without wrapper for simple case', function () {
    $view = $this->blade('<x-hwc::input name="email" />');

    $view->assertDontSee('<span class="hwc-input"', false);
    $view->assertSee('<input', false);
    $view->assertSee('name="email"', false);
});

it('uses default type=text', function () {
    $view = $this->blade('<x-hwc::input name="email" />');

    $view->assertSee('type="text"', false);
});

it('passes through type prop', function () {
    $view = $this->blade('<x-hwc::input name="email" type="email" />');

    $view->assertSee('type="email"', false);
});

// --- Id derivation ---

it('derives id from name', function () {
    $view = $this->blade('<x-hwc::input name="email" />');

    $view->assertSee('id="email"', false);
});

it('derives id from bracket notation', function () {
    $view = $this->blade('<x-hwc::input name="variables[0][name]" />');

    $view->assertSee('id="variables-0-name"', false);
});

it('uses explicit id', function () {
    $view = $this->blade('<x-hwc::input name="email" id="my-input" />');

    $view->assertSee('id="my-input"', false);
});

// --- Value + old() ---

it('renders value prop', function () {
    $view = $this->blade('<x-hwc::input name="email" value="hello" />');

    $view->assertSee('value="hello"', false);
});

it('merges value with old() input', function () {
    session()->put('_old_input', ['email' => 'old-value']);

    $view = $this->blade('<x-hwc::input name="email" value="default" />');

    $view->assertSee('value="old-value"', false);
});

it('disables old() when :old=false', function () {
    session()->put('_old_input', ['email' => 'old-value']);

    $view = $this->blade('<x-hwc::input name="email" value="default" :old="false" />');

    $view->assertSee('value="default"', false);
    $view->assertDontSee('value="old-value"', false);
});

it('uses old() with derived dot-notation key for array names', function () {
    session()->put('_old_input', ['variables' => [0 => ['name' => 'flashed']]]);

    $view = $this->blade('<x-hwc::input name="variables[0][name]" />');

    $view->assertSee('value="flashed"', false);
});

// --- Error key + ARIA ---

it('always sets aria-describedby pointing to error id', function () {
    $view = $this->blade('<x-hwc::input name="email" />');

    $view->assertSee('aria-describedby="email-error"', false);
});

it('sets aria-invalid and data-invalid when error present', function () {
    shareInputErrors(['email' => ['Required']]);

    $view = $this->blade('<x-hwc::input name="email" />');

    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('data-invalid', false);
});

it('does not set aria-invalid when no errors', function () {
    $view = $this->blade('<x-hwc::input name="email" />');

    $view->assertDontSee('aria-invalid="true"', false);
    $view->assertDontSee('data-invalid', false);
});

it('uses derived error key from bracket notation', function () {
    shareInputErrors(['variables.0.name' => ['Required']]);

    $view = $this->blade('<x-hwc::input name="variables[0][name]" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('uses explicit error key override', function () {
    shareInputErrors(['custom.path' => ['Required']]);

    $view = $this->blade('<x-hwc::input name="email" error-key="custom.path" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('sets aria-required when required attribute is present', function () {
    $view = $this->blade('<x-hwc::input name="email" required />');

    $view->assertSee('aria-required="true"', false);
});

// --- Element controllers (no wrapper) ---

it('adds auto-select controller when auto-select prop is true', function () {
    $view = $this->blade('<x-hwc::input name="email" auto-select />');

    $view->assertSee('data-controller="auto-select"', false);
});

it('adds input-mask controller when mask is given', function () {
    $view = $this->blade('<x-hwc::input name="phone" mask="phone-br" />');

    $view->assertSee('data-controller="input-mask"', false);
});

it('resolves mask preset to mask string', function () {
    $view = $this->blade('<x-hwc::input name="cpf" mask="cpf" />');

    $view->assertSee('data-input-mask-mask-value="###.###.###-##"', false);
});

it('passes through raw mask string when not a preset', function () {
    $view = $this->blade('<x-hwc::input name="custom" mask="@@-##" />');

    $view->assertSee('data-input-mask-mask-value="@@-##"', false);
});

it('combines element controllers with a space', function () {
    $view = $this->blade('<x-hwc::input name="email" auto-select mask="cpf" />');

    $view->assertSee('data-controller="auto-select input-mask"', false);
});

// --- Wrapper: clearable ---

it('renders wrapper with clear-input controller when clearable', function () {
    $view = $this->blade('<x-hwc::input name="q" clearable />');

    $view->assertSee('data-controller="clear-input"', false);
    $view->assertSee('data-clear-input-target="input"', false);
    $view->assertSee('data-clear-input-target="clearButton"', false);
});

it('renders clear button with type=button', function () {
    $view = $this->blade('<x-hwc::input name="q" clearable />');

    $view->assertSee('type="button"', false);
});

// --- Wrapper: combination ---

it('combines element + wrapper controllers correctly', function () {
    $view = $this->blade('<x-hwc::input name="q" clearable mask="cpf" auto-select />');

    // Wrapper has clear-input
    $view->assertSee('data-controller="clear-input"', false);
    // Input has element controllers
    $view->assertSee('data-controller="auto-select input-mask"', false);
});

// --- Class merge ---

it('merges class on input element', function () {
    $view = $this->blade('<x-hwc::input name="email" class="border" />');

    $view->assertSee('class="border"', false);
});

it('merges wrapper-class on wrapper when present', function () {
    $view = $this->blade('<x-hwc::input name="q" clearable wrapper-class="relative" />');

    $view->assertSee('relative', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hwc::input name="email" placeholder="you@example.com" data-test="x" />');

    $view->assertSee('placeholder="you@example.com"', false);
    $view->assertSee('data-test="x"', false);
});
