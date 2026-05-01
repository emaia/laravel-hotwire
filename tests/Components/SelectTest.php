<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function shareSelectErrors(array $errorsByKey): void
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

it('renders a select element', function () {
    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\', 2 => \'Inactive\']" />');

    $view->assertSee('<select', false);
    $view->assertSee('name="status"', false);
});

it('renders options from the options array', function () {
    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\', 2 => \'Inactive\']" />');

    $view->assertSee('value="1"', false);
    $view->assertSee('Active');
    $view->assertSee('value="2"', false);
    $view->assertSee('Inactive');
});

it('renders a single option', function () {
    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\']" />');

    $view->assertSee('value="1"', false);
    $view->assertSee('Active');
});

it('renders empty select when no options', function () {
    $view = $this->blade('<x-hwc::select name="status" />');

    $view->assertSee('<select', false);
    $view->assertSee('</select>', false);
});

// --- Id derivation ---

it('derives id from name', function () {
    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\']" />');

    $view->assertSee('id="status"', false);
});

it('derives id from bracket notation', function () {
    $view = $this->blade('<x-hwc::select name="variables[0][status]" :options="[1 => \'Active\']" />');

    $view->assertSee('id="variables-0-status"', false);
});

it('uses explicit id', function () {
    $view = $this->blade('<x-hwc::select name="status" id="my-select" :options="[1 => \'Active\']" />');

    $view->assertSee('id="my-select"', false);
});

// --- Selected ---

it('marks the selected option', function () {
    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\', 2 => \'Inactive\']" :selected="2" />');

    $view->assertSee('selected', false);
});

it('does not mark any option when selected is null', function () {
    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\', 2 => \'Inactive\']" />');

    $view->assertDontSee('selected', false);
});

it('matches selected with loose comparison for string/int', function () {
    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\', 2 => \'Inactive\']" selected="2" />');

    $view->assertSee('selected', false);
});

// --- Value + old() ---

it('merges selected with old() input', function () {
    session()->put('_old_input', ['status' => '2']);

    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\', 2 => \'Inactive\']" :selected="1" />');

    $view->assertSee('selected', false);
});

it('disables old() when :old=false', function () {
    session()->put('_old_input', ['status' => '2']);

    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\', 2 => \'Inactive\']" :selected="1" :old="false" />');

    // :old=false means old() is ignored, so selected=1 should be the result
    $view->assertSee('selected', false);
});

it('uses old() with derived dot-notation key for array names', function () {
    session()->put('_old_input', ['variables' => [0 => ['status' => 'active']]]);

    $view = $this->blade('<x-hwc::select name="variables[0][status]" :options="[\'active\' => \'Active\', \'inactive\' => \'Inactive\']" />');

    $view->assertSee('value="active"', false);
    $view->assertSee('selected', false);
});

// --- Placeholder ---

it('renders placeholder option when provided', function () {
    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\']" placeholder="Choose..." />');

    $view->assertSee('value=""', false);
    $view->assertSee('disabled', false);
    $view->assertSee('Choose...');
});

it('does not select placeholder when a value is selected', function () {
    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\']" placeholder="Choose..." :selected="1" />');

    $view->assertSee('value="1" selected', false);
});

// --- Error key + ARIA ---

it('always sets aria-describedby pointing to error id', function () {
    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\']" />');

    $view->assertSee('aria-describedby="status-error"', false);
});

it('sets aria-invalid and data-invalid when error present', function () {
    shareSelectErrors(['status' => ['Required']]);

    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\']" />');

    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('data-invalid', false);
});

it('does not set aria-invalid when no errors', function () {
    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\']" />');

    $view->assertDontSee('aria-invalid="true"', false);
    $view->assertDontSee('data-invalid', false);
});

it('uses explicit error key override', function () {
    shareSelectErrors(['custom.path' => ['Required']]);

    $view = $this->blade('<x-hwc::select name="status" error-key="custom.path" :options="[1 => \'Active\']" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('sets aria-required when required attribute is present', function () {
    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\']" required />');

    $view->assertSee('aria-required="true"', false);
});

// --- Class merge ---

it('merges custom class on select element', function () {
    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\']" class="w-full" />');

    $view->assertSee('class="w-full"', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hwc::select name="status" :options="[1 => \'Active\']" disabled data-test="x" />');

    $view->assertSee('disabled', false);
    $view->assertSee('data-test="x"', false);
});

// --- @aware propagation from field ---

it('picks up name and required from field via @aware', function () {
    $view = $this->blade('
        <x-hwc::field name="status" required>
            <x-hwc::select :options="[1 => \'Active\', 2 => \'Inactive\']" />
        </x-hwc::field>
    ');

    $view->assertSee('name="status"', false);
    $view->assertSee('id="status"', false);
    $view->assertSee('aria-required="true"', false);
});
