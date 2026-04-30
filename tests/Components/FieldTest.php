<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function shareFieldErrors(array $errorsByKey): void
{
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag($errorsByKey));
    view()->share('errors', $bag);
}

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
    request()->setLaravelSession($this->app['session.store']);
});

// --- Wrapper structure ---

it('renders a wrapper div with hwc-field class', function () {
    $view = $this->blade('<x-hwc::field name="email"><span>x</span></x-hwc::field>');

    $view->assertSee('hwc-field', false);
    $view->assertSee('<span>x</span>', false);
});

it('merges custom class on wrapper', function () {
    $view = $this->blade('<x-hwc::field name="email" class="space-y-1"><span>x</span></x-hwc::field>');

    $view->assertSee('hwc-field', false);
    $view->assertSee('space-y-1', false);
});

// --- Auto label ---

it('renders label when label prop is provided', function () {
    $view = $this->blade('<x-hwc::field name="email" label="E-mail"><span>x</span></x-hwc::field>');

    $view->assertSee('<label', false);
    $view->assertSee('for="email"', false);
    $view->assertSee('E-mail');
});

it('does not render label when label prop is absent', function () {
    $view = $this->blade('<x-hwc::field name="email"><span>x</span></x-hwc::field>');

    $view->assertDontSee('<label', false);
});

// --- Description ---

it('renders description when provided', function () {
    $view = $this->blade('<x-hwc::field name="email" label="E-mail" description="We will not share."><span>x</span></x-hwc::field>');

    $view->assertSee('hwc-description', false);
    $view->assertSee('We will not share.');
});

// --- Always renders error component ---

it('always renders an error container', function () {
    $view = $this->blade('<x-hwc::field name="email"><span>x</span></x-hwc::field>');

    $view->assertSee('id="email-error"', false);
    $view->assertSee('role="alert"', false);
});

it('renders error messages from $errors when present', function () {
    shareFieldErrors(['email' => ['Required']]);

    $view = $this->blade('<x-hwc::field name="email"><span>x</span></x-hwc::field>');

    $view->assertSee('Required');
});

// --- Required propagation ---

it('renders required marker on label when required is true', function () {
    $view = $this->blade('<x-hwc::field name="email" label="E-mail" required><span>x</span></x-hwc::field>');

    $view->assertSee('hwc-required', false);
});

// --- Aware: child input picks up name ---

it('propagates name to nested input via @aware', function () {
    $view = $this->blade('
        <x-hwc::field name="email" label="E-mail">
            <x-hwc::input type="email" />
        </x-hwc::field>
    ');

    $view->assertSee('id="email"', false);
    $view->assertSee('name="email"', false);
    $view->assertSee('aria-describedby="email-error"', false);
});

it('propagates name with bracket notation to nested input', function () {
    $view = $this->blade('
        <x-hwc::field name="variables[0][name]" label="Variables">
            <x-hwc::input type="text" />
        </x-hwc::field>
    ');

    $view->assertSee('name="variables[0][name]"', false);
    $view->assertSee('id="variables-0-name"', false);
    $view->assertSee('for="variables-0-name"', false);
});

it('propagates required to nested input', function () {
    $view = $this->blade('
        <x-hwc::field name="email" label="E-mail" required>
            <x-hwc::input type="email" />
        </x-hwc::field>
    ');

    $view->assertSee('aria-required="true"', false);
});

// --- Override id from field ---

it('overrides derived id when explicit id is set on field', function () {
    $view = $this->blade('
        <x-hwc::field name="variables[0][name]" id="variable" label="Variables">
            <x-hwc::input type="text" />
        </x-hwc::field>
    ');

    $view->assertSee('id="variable"', false);
    $view->assertSee('for="variable"', false);
    $view->assertSee('id="variable-error"', false);
});

// --- Override errorKey from field ---

it('overrides errorKey when explicit error-key is set on field', function () {
    shareFieldErrors(['indicator.name' => ['Required']]);

    $view = $this->blade('
        <x-hwc::field name="variables[0][name]" error-key="indicator.name" label="Variables">
            <x-hwc::input type="text" />
        </x-hwc::field>
    ');

    $view->assertSee('Required');
    $view->assertSee('aria-invalid="true"', false);
});
