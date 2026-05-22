<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function shareCheckboxGroupErrors(array $errorsByKey): void
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

it('renders checkboxes from options', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" />');

    $view->assertSee('name="ids[]"', false);
    $view->assertSee('value="1"', false);
    $view->assertSee('One');
    $view->assertSee('value="2"', false);
    $view->assertSee('Two');
});

it('renders a single checkbox', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="active" :options="[1 => \'Active\']" />');

    $view->assertSee('value="1"', false);
    $view->assertSee('Active');
});

// --- Non-associative options ---

it('normalizes flat options array so keys equal values', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="branchs[]" :options="[\'main\', \'dev\', \'next\']" :selected="[\'main\', \'dev\']" />');

    $view->assertSee('value="main"', false);
    $view->assertSee('value="dev"', false);
    $view->assertSee('value="next"', false);
    $view->assertSee('checked', false);
});

// --- Selected ---

it('checks selected values', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" :selected="[1]" />');

    $view->assertSee('checked', false);
});

it('checks multiple selected values', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\', 3 => \'Three\']" :selected="[1, 3]" />');

    $view->assertSee('checked', false);
});

it('does not check anything when selected is empty', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" />');

    $view->assertDontSee('checked', false);
});

// --- Value + old() ---

it('merges selected with old() input', function () {
    session()->put('_old_input', ['ids' => [2]]);

    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" :selected="[1]" />');

    $html = (string) $view;
    expect($html)->toContain('value="2"');
    expect($html)->toContain('checked');
    // Only one checkbox should be checked (old wins over selected)
    $this->assertEquals(1, substr_count($html, 'checked'));
});

it('disables old() when :old=false', function () {
    session()->put('_old_input', ['ids' => [2]]);

    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" :selected="[1]" :old="false" />');

    $html = (string) $view;
    // :old=false means selected [1] remains, old [2] is ignored
    expect($html)->toContain('value="1"');
    expect($html)->toContain('checked');
    $this->assertEquals(1, substr_count($html, 'checked'));
});

it('casts old() scalar value to array', function () {
    session()->put('_old_input', ['branch' => 'main']);

    $view = $this->blade('<x-hwc::checkbox-group name="branch[]" :options="[\'main\' => \'Main\', \'dev\' => \'Dev\']" :selected="[\'dev\']" />');

    $html = (string) $view;
    // Old scalar 'main' should be cast to array and checked
    expect($html)->toContain('value="main"');
    expect($html)->toContain('checked');
    $this->assertEquals(1, substr_count($html, 'checked'));
});

// --- Name auto-normalization ---

it('auto-appends [] when name does not end with brackets', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids" :options="[1 => \'One\', 2 => \'Two\']" />');

    $view->assertSee('name="ids[]"', false);
    $view->assertDontSee('name="ids"', false);
});

it('keeps name unchanged when it already ends with []', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" />');

    $view->assertSee('name="ids[]"', false);
});

it('normalizes name from @aware via field wrapper', function () {
    $view = $this->blade('
        <x-hwc::field name="ids">
            <x-hwc::checkbox-group :options="[1 => \'One\']" />
        </x-hwc::field>
    ');

    $view->assertSee('name="ids[]"', false);
});

it('uses unbracketed name for id and error key derivation after normalization', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids" :options="[1 => \'One\']" />');

    // id and aria-describedby still derive from the unbracketed name
    $view->assertSee('id="ids-1"', false);
    $view->assertSee('aria-describedby="ids-error"', false);
});

// --- Select all ---

it('does not add controller wrapper without select-all', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" />');

    $view->assertDontSee('data-controller', false);
});

it('adds controller wrapper with select-all', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" select-all />');

    $view->assertSee('data-controller="checkbox-select-all"', false);
    $view->assertSee('data-checkbox-select-all-target="checkboxAll"', false);
    $view->assertSee('data-checkbox-select-all-target="checkbox"', false);
});

it('renders select-all master checkbox with default label', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" select-all />');

    $view->assertSee('Select all');
});

it('renders select-all with custom label', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" select-all select-all-label="Marcar todos" />');

    $view->assertSee('Marcar todos');
    $view->assertDontSee('Select all');
});

it('marks individual checkboxes as checkbox targets only when select-all is active', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" select-all />');

    $view->assertSee('data-checkbox-select-all-target="checkbox"', false);

    $view2 = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" />');

    $view2->assertDontSee('data-checkbox-select-all-target', false);
});

// --- Class merge ---

it('merges custom class on wrapper', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" class="space-y-2" />');

    $view->assertSee('class="space-y-2"', false);
});

it('adds hwc-input hook on items', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" />');

    $view->assertSee('class="hwc-input"', false);
});

it('adds hwc-label hook on item wrappers', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" />');

    $view->assertSee('class="hwc-label"', false);
});

it('adds hwc-input and hwc-label hooks on the select-all master', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" select-all />');

    $html = (string) $view;
    expect(substr_count($html, 'class="hwc-input"'))->toBe(2);
    expect(substr_count($html, 'class="hwc-label"'))->toBe(2);
});

// --- User data-controller merge ---

it('merges user data-controller with checkbox-select-all when select-all', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" data-controller="foo" select-all />');

    $view->assertSee('data-controller="foo checkbox-select-all"', false);
});

it('preserves user data-controller when no select-all', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" data-controller="foo" />');

    $view->assertSee('data-controller="foo"', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" data-test="x" hidden />');

    $view->assertSee('data-test="x"', false);
    $view->assertSee('hidden', false);
});

// --- Explicit name pass-through ---

it('renders name when explicitly provided inside field', function () {
    $view = $this->blade('
        <x-hwc::field name="other[]">
            <x-hwc::checkbox-group name="custom[]" :options="[1 => \'One\']" />
        </x-hwc::field>
    ');

    $view->assertSee('name="custom[]"', false);
});

it('does not render name attribute when no name is provided', function () {
    $view = $this->blade('<x-hwc::checkbox-group :options="[1 => \'One\']" />');

    $view->assertDontSee('name="', false);
});

it('field overrides name from @aware', function () {
    $view = $this->blade('
        <x-hwc::field name="other[]">
            <x-hwc::checkbox-group name="custom[]" :options="[1 => \'One\']" />
        </x-hwc::field>
    ');

    $view->assertSee('name="custom[]"', false);
});

// --- Id derivation ---

it('generates unique ids per checkbox from name', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" />');

    $view->assertSee('id="ids-1"', false);
    $view->assertSee('id="ids-2"', false);
});

it('generates id from single name without brackets', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="active" :options="[1 => \'Active\']" />');

    $view->assertSee('id="active-1"', false);
});

it('uses explicit id as base for per-checkbox ids', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="branchs[]" id="my-group" :options="[\'main\' => \'Main\', \'dev\' => \'Dev\']" />');

    $view->assertSee('id="my-group-main"', false);
    $view->assertSee('id="my-group-dev"', false);
});

it('does not set id when no name and no explicit id', function () {
    $view = $this->blade('<x-hwc::checkbox-group :options="[1 => \'One\']" />');

    $view->assertDontSee('id="', false);
});

// --- ARIA ---

it('always sets aria-describedby on checkboxes', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" />');

    $view->assertSee('aria-describedby="ids-error"', false);
});

it('sets aria-invalid and data-invalid when error present', function () {
    shareCheckboxGroupErrors(['ids' => ['Required.']]);

    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" />');

    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('data-invalid', false);
});

it('does not set aria-invalid when no errors', function () {
    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" :options="[1 => \'One\']" />');

    $view->assertDontSee('aria-invalid="true"', false);
    $view->assertDontSee('data-invalid', false);
});

it('uses derived error key from bracket notation', function () {
    shareCheckboxGroupErrors(['variables.0.name' => ['Required.']]);

    $view = $this->blade('<x-hwc::checkbox-group name="variables[0][name]" :options="[\'a\' => \'A\']" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('uses explicit error key override', function () {
    shareCheckboxGroupErrors(['custom.path' => ['Required.']]);

    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" error-key="custom.path" :options="[1 => \'One\']" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('uses error key for error lookup, aria-describedby from name', function () {
    shareCheckboxGroupErrors(['custom' => ['Required.']]);

    $view = $this->blade('<x-hwc::checkbox-group name="ids[]" error-key="custom" :options="[1 => \'One\']" />');

    // aria-describedby follows the name-derived id, not the error key
    $view->assertSee('aria-describedby="ids-error"', false);
    // Errors looked up on the explicit error key
    $view->assertSee('aria-invalid="true"', false);
    // error-key prop is consumed by component, not leaked as DOM attribute
    $view->assertDontSee('error-key', false);
});
