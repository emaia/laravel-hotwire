<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function shareTextareaErrors(array $errorsByKey): void
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

it('renders a textarea element', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" />');

    $view->assertSee('<textarea', false);
    $view->assertSee('name="bio"', false);
});

it('renders content from value prop', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" value="Hello" />');

    $view->assertSee('>Hello</textarea>', false);
});

it('renders empty textarea when no value', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" />');

    $view->assertSee('></textarea>', false);
});

// --- Id derivation ---

it('derives id from name', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" />');

    $view->assertSee('id="bio"', false);
});

it('derives id from bracket notation', function () {
    $view = $this->blade('<x-hwc::textarea name="variables[0][name]" />');

    $view->assertSee('id="variables-0-name"', false);
});

it('uses explicit id', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" id="my-textarea" />');

    $view->assertSee('id="my-textarea"', false);
});

// --- Value + old() ---

it('renders value prop', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" value="hello" />');

    $view->assertSee('hello');
});

it('merges value with old() input', function () {
    session()->put('_old_input', ['bio' => 'old-value']);

    $view = $this->blade('<x-hwc::textarea name="bio" value="default" />');

    $view->assertSee('old-value');
    $view->assertDontSee('default');
});

it('disables old() when :old=false', function () {
    session()->put('_old_input', ['bio' => 'old-value']);

    $view = $this->blade('<x-hwc::textarea name="bio" value="default" :old="false" />');

    $view->assertSee('default');
    $view->assertDontSee('old-value');
});

it('uses old() with derived dot-notation key for array names', function () {
    session()->put('_old_input', ['variables' => [0 => ['name' => 'flashed']]]);

    $view = $this->blade('<x-hwc::textarea name="variables[0][name]" />');

    $view->assertSee('flashed');
});

// --- Error key + ARIA ---

it('always sets aria-describedby pointing to error id', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" />');

    $view->assertSee('aria-describedby="bio-error"', false);
});

it('sets aria-invalid and data-invalid when error present', function () {
    shareTextareaErrors(['bio' => ['Required']]);

    $view = $this->blade('<x-hwc::textarea name="bio" />');

    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('data-invalid', false);
});

it('does not set aria-invalid when no errors', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" />');

    $view->assertDontSee('aria-invalid="true"', false);
    $view->assertDontSee('data-invalid', false);
});

it('uses derived error key from bracket notation', function () {
    shareTextareaErrors(['variables.0.name' => ['Required']]);

    $view = $this->blade('<x-hwc::textarea name="variables[0][name]" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('uses explicit error key override', function () {
    shareTextareaErrors(['custom.path' => ['Required']]);

    $view = $this->blade('<x-hwc::textarea name="bio" error-key="custom.path" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('sets aria-required when required attribute is present', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" required />');

    $view->assertSee('aria-required="true"', false);
});

// --- auto-resize ---

it('adds auto-resize controller when auto-resize is true', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" auto-resize />');

    $view->assertSee('data-controller="auto-resize"', false);
});

it('does not add auto-resize by default', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" />');

    $view->assertDontSee('data-controller', false);
    $view->assertDontSee('auto-resize', false);
});

// --- counter ---

it('renders wrapper with char-counter controller when counter is set', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" :counter="160" />');

    $view->assertSee('data-controller="char-counter"', false);
    $view->assertSee('data-char-counter-target="input"', false);
    $view->assertSee('data-char-counter-target="counter"', false);
});

it('sets maxlength when counter is set', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" :counter="160" />');

    $view->assertSee('maxlength="160"', false);
});

it('sets countdown value when countdown is true', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" :counter="160" countdown />');

    $view->assertSee('data-char-counter-countdown-value="true"', false);
});

it('counter container has aria-live polite', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" :counter="160" />');

    $view->assertSee('aria-live="polite"', false);
});

it('does not render wrapper when no counter', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" />');

    $view->assertDontSee('data-controller="char-counter"', false);
    $view->assertDontSee('data-char-counter-target="counter"', false);
});

// --- Combination ---

it('combines auto-resize with counter correctly', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" auto-resize :counter="160" />');

    // wrapper has char-counter
    $view->assertSee('data-controller="char-counter"', false);
    // textarea has auto-resize
    $view->assertSee('data-controller="auto-resize"', false);
    // textarea is also counter target
    $view->assertSee('data-char-counter-target="input"', false);
});

// --- Class merge ---

it('merges class on textarea element', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" class="w-full" />');

    $view->assertSee('class="w-full"', false);
});

it('merges wrapper-class on wrapper when present', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" :counter="160" wrapper-class="relative" />');

    $view->assertSee('relative', false);
});

// --- User data-controller merge ---

it('merges user data-controller with auto-resize', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" data-controller="auto-select" auto-resize />');

    $view->assertSee('data-controller="auto-select auto-resize"', false);
});

it('filters data-char-counter prefix when counter is active', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" :counter="100" data-char-counter-target="override" />');

    $view->assertDontSee('data-char-counter-target="override"', false);
});

it('passes user data when no wrapper exists', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" data-controller="foo" data-foo-value="bar" />');

    $view->assertSee('data-controller="foo"', false);
    $view->assertSee('data-foo-value="bar"', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hwc::textarea name="bio" placeholder="Tell us..." rows="4" />');

    $view->assertSee('placeholder="Tell us..."', false);
    $view->assertSee('rows="4"', false);
});

// --- @aware propagation from field ---

it('picks up name and required from field via @aware', function () {
    $view = $this->blade('
        <x-hwc::field name="bio" required>
            <x-hwc::textarea auto-resize />
        </x-hwc::field>
    ');

    $view->assertSee('name="bio"', false);
    $view->assertSee('id="bio"', false);
    $view->assertSee('aria-required="true"', false);
});
