<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function shareSwitchErrors(array $errorsByKey): void
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

// --- Basic render ---

it('renders a native checkbox with switch role', function () {
    $view = $this->blade('<x-hw::switch name="enabled" value="1" />');

    $view->assertSee('<input', false);
    $view->assertSee('type="checkbox"', false);
    $view->assertSee('role="switch"', false);
    $view->assertSee('data-slot="switch"', false);
    $view->assertSee('data-checkable="true"', false);
    $view->assertSee('data-size="default"', false);
    $view->assertSee('name="enabled"', false);
    $view->assertSee('value="1"', false);
});

it('renders a small switch size', function () {
    $view = $this->blade('<x-hw::switch name="enabled" size="sm" />');

    $view->assertSee('data-size="sm"', false);
});

it('emits data-disabled when disabled', function () {
    $view = $this->blade('<x-hw::switch name="enabled" disabled />');

    $view->assertSee('disabled', false);
    $view->assertSee('data-disabled="true"', false);
});

it('does not expose indeterminate API', function () {
    $view = $this->blade('<x-hw::switch name="enabled" />');

    $view->assertDontSee('data-controller="checkbox"', false);
    $view->assertDontSee('data-checkbox-indeterminate-value', false);
});

// --- Checked + old() ---

it('renders checked attribute from checked prop', function () {
    $view = $this->blade('<x-hw::switch name="enabled" :checked="true" />');

    $view->assertSee('checked', false);
});

it('restores checked state from old input when the key is present', function () {
    session()->put('_old_input', ['enabled' => '1']);

    $view = $this->blade('<x-hw::switch name="enabled" :checked="false" />');

    $view->assertSee('checked', false);
});

it('restores unchecked state from old input when the key is absent', function () {
    session()->put('_old_input', ['other' => '1']);

    $view = $this->blade('<x-hw::switch name="enabled" :checked="true" />');

    $view->assertDontSee(' checked', false);
});

it('can opt out of old input restoration', function () {
    session()->put('_old_input', ['other' => '1']);

    $view = $this->blade('<x-hw::switch name="enabled" :checked="true" :old="false" />');

    $view->assertSee('checked', false);
});

// --- Unchecked hidden value ---

it('renders hidden unchecked value only when requested', function () {
    $withoutHidden = (string) $this->blade('<x-hw::switch name="enabled" value="1" />');
    $withHidden = (string) $this->blade('<x-hw::switch name="enabled" value="1" unchecked-value="0" />');

    expect($withoutHidden)->not->toContain('type="hidden"')
        ->and($withHidden)->toContain('type="hidden"')
        ->and($withHidden)->toContain('value="0"')
        ->and(substr_count($withHidden, 'name="enabled"'))->toBe(2);
});

// --- Auto-submit ---

it('can opt into auto-submit change action', function () {
    $view = $this->blade('<x-hw::switch name="enabled" auto-submit />');

    $view->assertSee('data-action="change->auto-submit#submit"', false);
});

// --- ARIA + field integration ---

it('sets aria-describedby and validation state', function () {
    shareSwitchErrors(['enabled' => ['Required.']]);

    $view = $this->blade('<x-hw::switch name="enabled" />');

    $view->assertSee('aria-describedby="enabled-error"', false);
    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('data-invalid', false);
});

it('supports field label choice card composition', function () {
    $view = $this->blade('
        <x-hw::field.label>
            <x-hw::field name="enabled" orientation="horizontal" disabled>
                <x-hw::field.content>
                    <x-hw::field.title>Enable notifications</x-hw::field.title>
                    <x-hw::field.description>Receive notifications.</x-hw::field.description>
                </x-hw::field.content>

                <x-hw::switch value="1" disabled />
            </x-hw::field>
        </x-hw::field.label>
    ');

    $html = (string) $view;
    expect($html)->toContain('data-slot="field-label"')
        ->and($html)->toContain('data-slot="field"')
        ->and($html)->toContain('data-disabled="true"')
        ->and($html)->toContain('data-slot="switch"')
        ->and($html)->toContain('Enable notifications');
});

it('inherits name and required state from field', function () {
    $view = $this->blade('
        <x-hw::field name="enabled" required>
            <x-hw::switch />
        </x-hw::field>
    ');

    $view->assertSee('name="enabled"', false);
    $view->assertSee('required', false);
    $view->assertSee('aria-required="true"', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hw::switch name="enabled" disabled data-test="x" />');

    $view->assertSee('disabled', false);
    $view->assertSee('data-test="x"', false);
});
