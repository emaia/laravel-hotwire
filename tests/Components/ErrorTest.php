<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function shareErrors(array $errorsByKey): void
{
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag($errorsByKey));
    view()->share('errors', $bag);
}

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
});

// --- Container always present ---

it('always renders the container even without errors but hides it', function () {
    $view = $this->blade('<x-hwc::error name="email" />');

    $view->assertSee('role="alert"', false);
    $view->assertSee('aria-live="polite"', false);
    $view->assertSee('hidden', false);
    $view->assertSee('id="email-error"', false);
});

it('does not show hidden when there are messages', function () {
    shareErrors(['email' => ['Required']]);

    $view = $this->blade('<x-hwc::error name="email" />');

    $view->assertDontSee(' hidden ', false);
    $view->assertDontSee(' hidden>', false);
    $view->assertSee('Required');
});

// --- Single vs multiple messages ---

it('renders a single message inline without list', function () {
    shareErrors(['email' => ['The email is required.']]);

    $view = $this->blade('<x-hwc::error name="email" />');

    $view->assertSee('The email is required.');
    $view->assertDontSee('<ul', false);
    $view->assertDontSee('<li', false);
});

it('renders multiple messages as a list', function () {
    shareErrors(['email' => ['The email is required.', 'The email must be valid.']]);

    $view = $this->blade('<x-hwc::error name="email" />');

    $view->assertSee('<ul', false);
    $view->assertSee('<li', false);
    $view->assertSee('The email is required.');
    $view->assertSee('The email must be valid.');
});

// --- Error key derivation ---

it('derives error key from name in bracket notation', function () {
    shareErrors(['variables.0.name' => ['Required']]);

    $view = $this->blade('<x-hwc::error name="variables[0][name]" />');

    $view->assertSee('Required');
    $view->assertSee('id="variables-0-name-error"', false);
});

it('uses explicit error key when provided', function () {
    shareErrors(['indicator.name' => ['Required']]);

    $view = $this->blade('<x-hwc::error name="ignored" error-key="indicator.name" />');

    $view->assertSee('Required');
});

// --- Override messages ---

it('uses explicit messages prop overriding $errors lookup', function () {
    shareErrors(['email' => ['Should not appear']]);

    $view = $this->blade('<x-hwc::error name="email" :messages="[\'Custom message\']" />');

    $view->assertSee('Custom message');
    $view->assertDontSee('Should not appear');
});

it('accepts a single string in messages', function () {
    $view = $this->blade('<x-hwc::error :messages="\'Just one\'" />');

    $view->assertSee('Just one');
});

it('renders multiple explicit messages as a list', function () {
    $view = $this->blade('<x-hwc::error :messages="[\'First error\', \'Second error\']" />');

    $view->assertSee('First error');
    $view->assertSee('Second error');
    $view->assertSee('<ul', false);
    $view->assertSee('<li', false);
});

// --- Id ---

it('uses explicit id', function () {
    $view = $this->blade('<x-hwc::error name="email" id="my-error" />');

    $view->assertSee('id="my-error"', false);
});

it('uses fallback id when no name and no id', function () {
    $view = $this->blade('<x-hwc::error :messages="[\'Oops\']" />');

    $view->assertSee('id="hwc-error-', false);
});

// --- Class merge ---

it('merges custom class on the error element', function () {
    $view = $this->blade('<x-hwc::error name="email" class="text-red-600" />');

    $view->assertSee('data-slot="error"', false);
    $view->assertSee('class="text-red-600"', false);
});

// --- @aware propagation from field ---

it('picks up name from field via @aware', function () {
    shareErrors(['email' => ['Required']]);

    $view = $this->blade('
        <x-hwc::field name="email">
            <x-hwc::error />
        </x-hwc::field>
    ');

    $view->assertSee('Required');
    $view->assertSee('id="email-error"', false);
});
