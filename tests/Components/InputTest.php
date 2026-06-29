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

it('generates random id when name is absent', function () {
    $view = $this->blade('<x-hwc::input />');

    $view->assertSee('id="hwc-input-', false);
    $view->assertDontSee('name="', false);
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

    $view->assertSee('border', false);
});

it('merges wrapper-class on wrapper when present', function () {
    $view = $this->blade('<x-hwc::input name="q" clearable wrapper-class="relative" />');

    $view->assertSee('relative', false);
});

// --- User data-controller merge ---

it('merges user data-controller with element controllers on input', function () {
    $view = $this->blade('<x-hwc::input name="q" data-controller="foo" auto-select mask="cpf" />');

    $view->assertSee('data-controller="foo auto-select input-mask"', false);
});

it('merges user data-controller onto the input element with wrapper', function () {
    $view = $this->blade('<x-hwc::input name="q" data-controller="auto-select" clearable />');

    $view->assertSee('data-controller="auto-select"', false);
});

it('passes user data-identifier-* when the controller is not internal', function () {
    $view = $this->blade('<x-hwc::input name="email" data-controller="foo" data-foo-value="bar" />');

    $view->assertSee('data-foo-value="bar"', false);
});

it('filters internal data-clear-input-* regardless of user merge', function () {
    $view = $this->blade('<x-hwc::input name="q" data-controller="foo" data-clear-input-target="override" clearable />');

    $view->assertSee('data-controller="foo"', false);
    $view->assertDontSee('data-clear-input-target="override"', false);
});

it('filters data-input-mask-* only when mask is active', function () {
    $view = $this->blade('<x-hwc::input name="phone" data-input-mask-mask-value="override" />');

    $view->assertSee('data-input-mask-mask-value="override"', false);
});

it('filters data-input-mask-* when mask is active', function () {
    $view = $this->blade('<x-hwc::input name="phone" mask="cpf" data-input-mask-mask-value="override" />');

    $view->assertDontSee('data-input-mask-mask-value="override"', false);
});

// --- Checkbox / radio ---

it('checkbox renders checked attribute from :checked prop', function () {
    $view = $this->blade('<x-hwc::input type="checkbox" name="notify" :checked="true" />');

    $view->assertSee('type="checkbox"', false);
    $view->assertSee('checked', false);
});

it('checkbox does not render checked when :checked is false', function () {
    $view = $this->blade('<x-hwc::input type="checkbox" name="notify" :checked="false" />');

    $view->assertSee('type="checkbox"', false);
    $view->assertDontSee(' checked', false);
});

it('checkbox does not run old() into value attribute', function () {
    session()->put('_old_input', ['notify' => 'on']);

    $view = $this->blade('<x-hwc::input type="checkbox" name="notify" />');

    $view->assertDontSee('value="on"', false);
});

it('single checkbox restores checked state from old() after validation', function () {
    session()->put('_old_input', ['notify' => 'on']);

    $view = $this->blade('<x-hwc::input type="checkbox" name="notify" />');

    $view->assertSee('checked', false);
});

it('single checkbox stays unchecked when old() has no key for it', function () {
    session()->put('_old_input', ['other' => 'x']);

    $view = $this->blade('<x-hwc::input type="checkbox" name="notify" :checked="true" />');

    $view->assertDontSee(' checked', false);
});

it('array checkbox restores checked state when value is in old() array', function () {
    session()->put('_old_input', ['roles' => ['admin', 'editor']]);

    $view = $this->blade('<x-hwc::input type="checkbox" name="roles[]" value="admin" />');

    $view->assertSee('value="admin"', false);
    $view->assertSee('checked', false);
});

it('array checkbox stays unchecked when value not in old() array', function () {
    session()->put('_old_input', ['roles' => ['admin']]);

    $view = $this->blade('<x-hwc::input type="checkbox" name="roles[]" value="editor" :checked="true" />');

    $view->assertSee('value="editor"', false);
    $view->assertDontSee(' checked', false);
});

it('array checkbox auto-derives unique id from name and value slug', function () {
    $view = $this->blade('<x-hwc::input type="checkbox" name="size[]" value="default" />');

    $view->assertSee('id="size-default"', false);
});

it('array checkbox slugs non-alpha value characters', function () {
    $view = $this->blade('<x-hwc::input type="checkbox" name="tags[]" value="Hello World" />');

    $view->assertSee('id="tags-hello-world"', false);
});

it('radio auto-derives unique id from name and value slug', function () {
    $view = $this->blade('<x-hwc::input type="radio" name="plan" value="pro" />');

    $view->assertSee('id="plan-pro"', false);
});

it('aria-describedby points to base error id, not the value-slugged id', function () {
    $view = $this->blade('<x-hwc::input type="radio" name="plan" value="pro" />');

    $view->assertSee('id="plan-pro"', false);
    $view->assertSee('aria-describedby="plan-error"', false);
});

it('explicit id wins over auto-derivation for group inputs', function () {
    $view = $this->blade('<x-hwc::input type="radio" name="plan" value="pro" id="custom-id" />');

    $view->assertSee('id="custom-id"', false);
    $view->assertSee('aria-describedby="custom-id-error"', false);
});

it('single checkbox with explicit value does not get value slug appended to id', function () {
    $view = $this->blade('<x-hwc::input type="checkbox" name="agree" value="yes" />');

    $view->assertSee('id="agree"', false);
});

it('group input with empty-slug value falls back to base id', function () {
    $view = $this->blade('<x-hwc::input type="radio" name="plan" value="!!!" />');

    $view->assertSee('id="plan"', false);
});

it('exclusive checkboxes sharing a name compare value scalar to old()', function () {
    session()->put('_old_input', ['size' => 'comfortable']);

    $defaultView = $this->blade('<x-hwc::input type="checkbox" name="size" value="default" />');
    $comfortableView = $this->blade('<x-hwc::input type="checkbox" name="size" value="comfortable" />');
    $compactView = $this->blade('<x-hwc::input type="checkbox" name="size" value="compact" />');

    $defaultView->assertDontSee(' checked', false);
    $comfortableView->assertSee('checked', false);
    $compactView->assertDontSee(' checked', false);
});

it('array checkbox derives error key without trailing dot', function () {
    shareInputErrors(['roles' => ['Required']]);

    $view = $this->blade('<x-hwc::input type="checkbox" name="roles[]" value="admin" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('radio renders checked attribute from :checked prop', function () {
    $view = $this->blade('<x-hwc::input type="radio" name="plan" value="pro" :checked="true" />');

    $view->assertSee('type="radio"', false);
    $view->assertSee('value="pro"', false);
    $view->assertSee('checked', false);
});

it('radio restores checked state from old() when value matches', function () {
    session()->put('_old_input', ['plan' => 'pro']);

    $view = $this->blade('<x-hwc::input type="radio" name="plan" value="pro" />');

    $view->assertSee('checked', false);
});

it('radio stays unchecked when old() value does not match', function () {
    session()->put('_old_input', ['plan' => 'basic']);

    $view = $this->blade('<x-hwc::input type="radio" name="plan" value="pro" :checked="true" />');

    $view->assertDontSee(' checked', false);
});

it('checkable ignores :old=false and still derives checked from old()', function () {
    session()->put('_old_input', ['notify' => 'on']);

    $view = $this->blade('<x-hwc::input type="checkbox" name="notify" :old="false" :checked="false" />');

    $view->assertDontSee(' checked', false);
});

// --- Checkable no-ops ---

it('does not attach input-mask controller for checkbox', function () {
    $view = $this->blade('<x-hwc::input type="checkbox" name="agree" mask="cpf" />');

    $view->assertDontSee('data-controller="input-mask"', false);
    $view->assertDontSee('data-input-mask-mask-value', false);
});

it('does not attach input-mask controller for radio', function () {
    $view = $this->blade('<x-hwc::input type="radio" name="plan" value="pro" mask="cpf" />');

    $view->assertDontSee('data-controller="input-mask"', false);
    $view->assertDontSee('data-input-mask-mask-value', false);
});

it('does not attach auto-select controller for checkbox', function () {
    $view = $this->blade('<x-hwc::input type="checkbox" name="agree" auto-select />');

    $view->assertDontSee('data-controller="auto-select"', false);
});

it('does not attach auto-select controller for radio', function () {
    $view = $this->blade('<x-hwc::input type="radio" name="plan" value="pro" auto-select />');

    $view->assertDontSee('data-controller="auto-select"', false);
});

it('does not render clearable wrapper for checkbox', function () {
    $view = $this->blade('<x-hwc::input type="checkbox" name="agree" clearable />');

    $view->assertDontSee('data-controller="clear-input"', false);
    $view->assertDontSee('data-clear-input-target', false);
});

it('does not render clearable wrapper for radio', function () {
    $view = $this->blade('<x-hwc::input type="radio" name="plan" value="pro" clearable />');

    $view->assertDontSee('data-controller="clear-input"', false);
    $view->assertDontSee('data-clear-input-target', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hwc::input name="email" placeholder="you@example.com" data-test="x" />');

    $view->assertSee('placeholder="you@example.com"', false);
    $view->assertSee('data-test="x"', false);
});
