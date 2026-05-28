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

it('does not auto-render label or description', function () {
    $view = $this->blade('<x-hwc::field name="email"><span>x</span></x-hwc::field>');

    $view->assertDontSee('<label', false);
    $view->assertDontSee('hwc-description', false);
});

// --- Auto-rendered error ---

it('auto-renders <x-hwc::error> at the end when name is set', function () {
    $view = $this->blade('<x-hwc::field name="email"><span>x</span></x-hwc::field>');

    $view->assertSee('id="email-error"', false);
    $view->assertSee('role="alert"', false);
});

it('auto-renders error with the field error key when name has bracket notation', function () {
    $view = $this->blade('<x-hwc::field name="variables[0][name]"><span>x</span></x-hwc::field>');

    $view->assertSee('id="variables-0-name-error"', false);
});

it('auto-renders error showing the validation message for the field name', function () {
    shareFieldErrors(['email' => ['Required']]);

    $view = $this->blade('<x-hwc::field name="email"><span>x</span></x-hwc::field>');

    $view->assertSee('Required');
});

it('does not auto-render error when name is not set', function () {
    $view = $this->blade('<x-hwc::field><span>x</span></x-hwc::field>');

    $view->assertDontSee('role="alert"', false);
});

it('does not auto-render error when :error="false"', function () {
    $view = $this->blade('<x-hwc::field name="email" :error="false"><span>x</span></x-hwc::field>');

    $view->assertDontSee('role="alert"', false);
});

it('does not duplicate the error node when slot already includes one', function () {
    $view = $this->blade('
        <x-hwc::field name="email" :error="false">
            <x-hwc::input type="email" />
            <x-hwc::error class="custom" />
        </x-hwc::field>
    ');

    expect(substr_count((string) $view, 'id="email-error"'))->toBe(1);
});

it('auto-rendered error uses field error-key override', function () {
    shareFieldErrors(['indicator.name' => ['Required']]);

    $view = $this->blade('
        <x-hwc::field name="variables[0][name]" error-key="indicator.name">
            <x-hwc::input type="text" />
        </x-hwc::field>
    ');

    $view->assertSee('Required');
});

// --- Auto-rendered label ---

it('auto-renders label before slot when label prop is provided', function () {
    $view = $this->blade('
        <x-hwc::field name="email" label="E-mail">
            <x-hwc::input type="email" />
        </x-hwc::field>
    ');

    $view->assertSee('<label', false);
    $view->assertSee('E-mail');
    $view->assertSee('for="email"', false);
});

it('auto-rendered label uses required-label prop', function () {
    $view = $this->blade('
        <x-hwc::field name="email" label="E-mail" required required-label="(obrigatório)">
            <x-hwc::input type="email" />
        </x-hwc::field>
    ');

    $view->assertSee('(obrigatório)');
    $view->assertDontSee('*', false);
});

it('auto-rendered label shows default asterisk when required', function () {
    $view = $this->blade('
        <x-hwc::field name="email" label="E-mail" required>
            <x-hwc::input type="email" />
        </x-hwc::field>
    ');

    $html = (string) $view;
    // The label contains '*' but rendered via component, not as raw '*'
    expect($html)->toContain('<span class="hwc-label-required"');
});

it('does not auto-render label when label prop is null', function () {
    $view = $this->blade('
        <x-hwc::field name="email">
            <x-hwc::input type="email" />
        </x-hwc::field>
    ');

    $view->assertDontSee('<label', false);
});

it('does not auto-render label when label prop is empty string', function () {
    $view = $this->blade('
        <x-hwc::field name="email" label="">
            <x-hwc::input type="email" />
        </x-hwc::field>
    ');

    $view->assertDontSee('<label', false);
});

it('auto-rendered label coexists with auto-rendered error', function () {
    $view = $this->blade('
        <x-hwc::field name="email" label="E-mail">
            <x-hwc::input type="email" />
        </x-hwc::field>
    ');

    $view->assertSee('for="email"', false);
    $view->assertSee('id="email-error"', false);
});

it('auto-rendered label appears before slot content', function () {
    $view = $this->blade('
        <x-hwc::field name="email" label="E-mail">
            <x-hwc::input type="email" value="test" />
        </x-hwc::field>
    ');

    $html = (string) $view;
    $labelPos = strpos($html, '<label');
    $inputPos = strpos($html, 'value="test"');
    expect($labelPos)->toBeLessThan($inputPos);
});

// --- Auto-rendered description ---

it('auto-renders description between slot and error when description prop is provided', function () {
    $view = $this->blade('
        <x-hwc::field name="email" description="Enter your work email address.">
            <x-hwc::input type="email" />
        </x-hwc::field>
    ');

    $view->assertSee('hwc-description', false);
    $view->assertSee('Enter your work email address.');
});

it('does not auto-render description when description prop is null', function () {
    $view = $this->blade('
        <x-hwc::field name="email">
            <x-hwc::input type="email" />
        </x-hwc::field>
    ');

    $view->assertDontSee('hwc-description', false);
});

it('does not auto-render description when description prop is empty string', function () {
    $view = $this->blade('
        <x-hwc::field name="email" description="">
            <x-hwc::input type="email" />
        </x-hwc::field>
    ');

    $view->assertDontSee('hwc-description', false);
});

it('auto-rendered description coexists with auto-rendered label', function () {
    $view = $this->blade('
        <x-hwc::field name="email" label="E-mail" description="We will never share your email.">
            <x-hwc::input type="email" />
        </x-hwc::field>
    ');

    $view->assertSee('<label', false);
    $view->assertSee('E-mail');
    $view->assertSee('hwc-description', false);
    $view->assertSee('We will never share your email.');
});

it('auto-rendered description appears after slot and before error', function () {
    $view = $this->blade('
        <x-hwc::field name="email" description="Helper text.">
            <x-hwc::input type="email" value="test" />
        </x-hwc::field>
    ');

    $html = (string) $view;
    $inputPos = strpos($html, 'value="test"');
    $descPos = strpos($html, 'hwc-description');
    $errorPos = strpos($html, 'id="email-error"');
    expect($inputPos)->toBeLessThan($descPos);
    expect($descPos)->toBeLessThan($errorPos);
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
