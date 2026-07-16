<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function shareCheckboxErrors(array $errorsByKey): void
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

it('renders a native checkbox input', function () {
    $view = $this->blade('<x-hw::checkbox name="notify" value="1" />');

    $view->assertSee('<input', false);
    $view->assertSee('type="checkbox"', false);
    $view->assertSee('data-slot="checkbox"', false);
    $view->assertSee('data-checkable="true"', false);
    $view->assertSee('name="notify"', false);
    $view->assertSee('value="1"', false);
});

it('does not render an empty value attribute when no value is provided', function () {
    $view = $this->blade('<x-hw::checkbox name="notify" />');

    $view->assertDontSee('value=""', false);
});

// --- Id derivation ---

it('derives id from name', function () {
    $view = $this->blade('<x-hw::checkbox name="notify" />');

    $view->assertSee('id="notify"', false);
});

it('derives id from bracket notation', function () {
    $view = $this->blade('<x-hw::checkbox name="settings[notify]" />');

    $view->assertSee('id="settings-notify"', false);
});

it('uses explicit id', function () {
    $view = $this->blade('<x-hw::checkbox name="notify" id="custom-id" />');

    $view->assertSee('id="custom-id"', false);
});

it('generates a stable prefix when name is absent', function () {
    $view = $this->blade('<x-hw::checkbox />');

    $view->assertSee('id="hw-checkbox-', false);
    $view->assertDontSee('name="', false);
});

// --- Checked + old() ---

it('renders checked attribute from checked prop', function () {
    $view = $this->blade('<x-hw::checkbox name="notify" :checked="true" />');

    $view->assertSee('checked', false);
});

it('does not render checked when checked prop is false', function () {
    $view = $this->blade('<x-hw::checkbox name="notify" :checked="false" />');

    $view->assertDontSee(' checked', false);
});

it('restores checked state from old input when the key is present', function () {
    session()->put('_old_input', ['notify' => '1']);

    $view = $this->blade('<x-hw::checkbox name="notify" :checked="false" />');

    $view->assertSee('checked', false);
});

it('restores unchecked state from old input when the key is absent', function () {
    session()->put('_old_input', ['other' => '1']);

    $view = $this->blade('<x-hw::checkbox name="notify" :checked="true" />');

    $view->assertDontSee(' checked', false);
});

it('can opt out of old input restoration', function () {
    session()->put('_old_input', ['other' => '1']);

    $view = $this->blade('<x-hw::checkbox name="notify" :checked="true" :old="false" />');

    $view->assertSee('checked', false);
});

it('checks array values from old input', function () {
    session()->put('_old_input', ['roles' => ['admin']]);

    $view = $this->blade('<x-hw::checkbox name="roles[]" value="admin" />');

    $view->assertSee('checked', false);
});

// --- Unchecked hidden value ---

it('renders hidden unchecked value only when requested', function () {
    $withoutHidden = (string) $this->blade('<x-hw::checkbox name="notify" value="1" />');
    $withHidden = (string) $this->blade('<x-hw::checkbox name="notify" value="1" unchecked-value="0" />');

    expect($withoutHidden)->not->toContain('type="hidden"')
        ->and($withHidden)->toContain('type="hidden"')
        ->and($withHidden)->toContain('value="0"')
        ->and(substr_count($withHidden, 'name="notify"'))->toBe(2);
});

it('renders hidden unchecked value before the checkbox', function () {
    $html = (string) $this->blade('<x-hw::checkbox name="notify" value="1" unchecked-value="0" />');

    expect(strpos($html, 'type="hidden"'))->toBeLessThan(strpos($html, 'type="checkbox"'));
});

// --- Indeterminate ---

it('activates the checkbox controller for indeterminate state', function () {
    $view = $this->blade('<x-hw::checkbox name="all" indeterminate />');

    $view->assertSee('data-controller="checkbox"', false);
    $view->assertSee('data-checkbox-indeterminate-value="true"', false);
});

it('merges user data-controller with indeterminate controller', function () {
    $view = $this->blade('<x-hw::checkbox name="all" indeterminate data-controller="analytics" />');

    $view->assertSee('data-controller="checkbox analytics"', false);
});

it('filters data-checkbox attributes when indeterminate is active', function () {
    $view = $this->blade('<x-hw::checkbox name="all" indeterminate data-checkbox-indeterminate-value="false" />');

    $view->assertDontSee('data-checkbox-indeterminate-value="false"', false);
    $view->assertSee('data-checkbox-indeterminate-value="true"', false);
});

// --- Auto-submit ---

it('can opt into auto-submit change action', function () {
    $view = $this->blade('<x-hw::checkbox name="notify" auto-submit />');

    $view->assertSee('data-action="change->auto-submit#submit"', false);
});

it('merges auto-submit with existing actions', function () {
    $view = $this->blade('<x-hw::checkbox name="notify" auto-submit data-action="change->analytics#track" />');

    $view->assertSee('data-action="change->auto-submit#submit change->analytics#track"', false);
});

it('can force debounced auto-submit with a field delay', function () {
    $view = $this->blade('<x-hw::checkbox name="notify" auto-submit="debounced" auto-submit-delay="600" />');

    $view->assertSee('data-action="change->auto-submit#debouncedSubmit"', false)
        ->assertSee('data-auto-submit-delay-param="600"', false)
        ->assertDontSee(' auto-submit-delay="600"', false);
});

// --- ARIA + field integration ---

it('sets aria-describedby and validation state', function () {
    shareCheckboxErrors(['notify' => ['Required.']]);

    $view = $this->blade('<x-hw::checkbox name="notify" />');

    $view->assertSee('aria-describedby="notify-error"', false);
    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('data-invalid', false);
});

it('inherits name and required state from field', function () {
    $view = $this->blade('
        <x-hw::field name="notify" required>
            <x-hw::checkbox />
        </x-hw::field>
    ');

    $view->assertSee('name="notify"', false);
    $view->assertSee('required', false);
    $view->assertSee('aria-required="true"', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hw::checkbox name="notify" disabled data-test="x" />');

    $view->assertSee('disabled', false);
    $view->assertSee('data-test="x"', false);
});
