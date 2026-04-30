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

it('renders a wrapper div with field class', function () {
    $view = $this->blade('<x-hwc::field name="email"><span>x</span></x-hwc::field>');

    $view->assertSee('field', false);
    $view->assertSee('<span>x</span>', false);
});

it('merges custom class on wrapper', function () {
    $view = $this->blade('<x-hwc::field name="email" class="space-y-1"><span>x</span></x-hwc::field>');

    $view->assertSee('field', false);
    $view->assertSee('space-y-1', false);
});

it('does not auto-render label, description or error', function () {
    $view = $this->blade('<x-hwc::field name="email"><span>x</span></x-hwc::field>');

    $view->assertDontSee('<label', false);
    $view->assertDontSee('hwc-description', false);
    $view->assertDontSee('hwc-error', false);
});

// --- @aware propagation ---

it('propagates name to nested input', function () {
    $view = $this->blade('
        <x-hwc::field name="email">
            <x-hwc::input type="email" />
        </x-hwc::field>
    ');

    $view->assertSee('id="email"', false);
    $view->assertSee('name="email"', false);
    $view->assertSee('aria-describedby="email-error"', false);
});

it('propagates name to nested label and error', function () {
    $view = $this->blade('
        <x-hwc::field name="email">
            <x-hwc::label>E-mail</x-hwc::label>
            <x-hwc::error />
        </x-hwc::field>
    ');

    $view->assertSee('for="email"', false);
    $view->assertSee('id="email-error"', false);
});

it('propagates name with bracket notation to nested children', function () {
    $view = $this->blade('
        <x-hwc::field name="variables[0][name]">
            <x-hwc::label>Variables</x-hwc::label>
            <x-hwc::input type="text" />
            <x-hwc::error />
        </x-hwc::field>
    ');

    $view->assertSee('name="variables[0][name]"', false);
    $view->assertSee('id="variables-0-name"', false);
    $view->assertSee('for="variables-0-name"', false);
    $view->assertSee('id="variables-0-name-error"', false);
});

it('propagates required to nested label and input', function () {
    $view = $this->blade('
        <x-hwc::field name="email" required>
            <x-hwc::label>E-mail</x-hwc::label>
            <x-hwc::input type="email" />
        </x-hwc::field>
    ');

    $view->assertSee('required', false);
    $view->assertSee('aria-required="true"', false);
});

// --- Override errorKey from field ---

it('overrides errorKey when explicit error-key is set on field', function () {
    shareFieldErrors(['indicator.name' => ['Required']]);

    $view = $this->blade('
        <x-hwc::field name="variables[0][name]" error-key="indicator.name">
            <x-hwc::input type="text" />
            <x-hwc::error />
        </x-hwc::field>
    ');

    $view->assertSee('Required');
    $view->assertSee('aria-invalid="true"', false);
});
