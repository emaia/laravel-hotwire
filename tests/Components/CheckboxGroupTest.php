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
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" />');

    $view->assertSee('name="ids[]"', false);
    $view->assertSee('value="1"', false);
    $view->assertSee('One');
    $view->assertSee('value="2"', false);
    $view->assertSee('Two');
});

it('renders a single checkbox', function () {
    $view = $this->blade('<x-hw::checkbox-group name="active" :options="[1 => \'Active\']" />');

    $view->assertSee('value="1"', false);
    $view->assertSee('Active');
});

it('renders rich item slot content alongside option checkboxes', function () {
    $view = $this->blade('
        <x-hw::checkbox-group name="roles[]" :options="[\'admin\' => \'Admin\']">
            <x-hw::checkbox-group.item value="editor">
                <span data-test="title">Editor</span>
                <span data-test="description">Can publish content.</span>
            </x-hw::checkbox-group.item>
        </x-hw::checkbox-group>
    ');

    $html = (string) $view;
    expect($html)->toContain('value="admin"')
        ->and($html)->toContain('Admin')
        ->and($html)->toContain('value="editor"')
        ->and($html)->toContain('data-test="title"')
        ->and($html)->toContain('Can publish content.');
});

// --- Non-associative options ---

it('normalizes flat options array so keys equal values', function () {
    $view = $this->blade('<x-hw::checkbox-group name="branches[]" :options="[\'main\', \'dev\', \'next\']" :selected="[\'main\', \'dev\']" />');

    $view->assertSee('value="main"', false);
    $view->assertSee('value="dev"', false);
    $view->assertSee('value="next"', false);
    $view->assertSee('checked', false);
});

// --- Selected ---

it('checks selected values', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" :selected="[1]" />');

    $view->assertSee('checked', false);
});

it('checks multiple selected values', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\', 3 => \'Three\']" :selected="[1, 3]" />');

    $view->assertSee('checked', false);
});

it('checks rich item values from selected values', function () {
    $view = $this->blade('
        <x-hw::checkbox-group name="roles[]" :selected="[\'editor\']">
            <x-hw::checkbox-group.item value="editor">Editor</x-hw::checkbox-group.item>
        </x-hw::checkbox-group>
    ');

    $view->assertSee('value="editor"', false);
    $view->assertSee('checked', false);
});

it('does not check anything when selected is empty', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" />');

    $view->assertDontSee('checked', false);
});

// --- Value + old() ---

it('merges selected with old() input', function () {
    session()->put('_old_input', ['ids' => [2]]);

    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" :selected="[1]" />');

    $html = (string) $view;
    expect($html)->toContain('value="2"')
        ->and($html)->toContain('checked');
    // Only one checkbox should be checked (old wins over selected)
    $this->assertEquals(1, substr_count($html, 'checked'));
});

it('disables old() when :old=false', function () {
    session()->put('_old_input', ['ids' => [2]]);

    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" :selected="[1]" :old="false" />');

    $html = (string) $view;
    // :old=false means selected [1] remains, old [2] is ignored
    expect($html)->toContain('value="1"')
        ->and($html)->toContain('checked');
    $this->assertEquals(1, substr_count($html, 'checked'));
});

it('casts old() scalar value to array', function () {
    session()->put('_old_input', ['branch' => 'main']);

    $view = $this->blade('<x-hw::checkbox-group name="branch[]" :options="[\'main\' => \'Main\', \'dev\' => \'Dev\']" :selected="[\'dev\']" />');

    $html = (string) $view;
    // Old scalar 'main' should be cast to array and checked
    expect($html)->toContain('value="main"')
        ->and($html)->toContain('checked');
    $this->assertEquals(1, substr_count($html, 'checked'));
});

it('restores rich item checked state from old input', function () {
    session()->put('_old_input', ['roles' => ['editor']]);

    $view = $this->blade('
        <x-hw::checkbox-group name="roles[]" :selected="[\'admin\']">
            <x-hw::checkbox-group.item value="admin">Admin</x-hw::checkbox-group.item>
            <x-hw::checkbox-group.item value="editor">Editor</x-hw::checkbox-group.item>
        </x-hw::checkbox-group>
    ');

    $html = (string) $view;
    expect($html)->toContain('value="editor"')
        ->and($html)->toContain('checked');
    $this->assertEquals(1, substr_count($html, 'checked'));
});

// --- Name auto-normalization ---

it('auto-appends [] when name does not end with brackets', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids" :options="[1 => \'One\', 2 => \'Two\']" />');

    $view->assertSee('name="ids[]"', false);
    $view->assertDontSee('name="ids"', false);
});

it('keeps name unchanged when it already ends with []', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" />');

    $view->assertSee('name="ids[]"', false);
});

it('normalizes name from @aware via field wrapper', function () {
    $view = $this->blade('
        <x-hw::field name="ids">
            <x-hw::checkbox-group :options="[1 => \'One\']" />
        </x-hw::field>
    ');

    $view->assertSee('name="ids[]"', false);
});

it('uses unbracketed name for id and error key derivation after normalization', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids" :options="[1 => \'One\']" />');

    // id and aria-describedby still derive from the unbracketed name
    $view->assertSee('id="ids-1"', false);
    $view->assertSee('aria-describedby="ids-error"', false);
});

// --- Select all ---

it('does not add controller wrapper without select-all', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" />');

    $view->assertDontSee('data-controller', false);
});

it('adds controller wrapper with select-all', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" select-all />');

    $view->assertSee('data-controller="checkbox-select-all"', false);
    $view->assertSee('data-checkbox-select-all-target="checkboxAll"', false);
    $view->assertSee('data-checkbox-select-all-target="checkbox"', false);
});

it('marks rich items as select-all targets when select-all is active', function () {
    $view = $this->blade('
        <x-hw::checkbox-group name="roles[]" select-all>
            <x-hw::checkbox-group.item value="editor">Editor</x-hw::checkbox-group.item>
        </x-hw::checkbox-group>
    ');

    $view->assertSee('data-checkbox-select-all-target="checkbox"', false);
});

it('renders select-all master checkbox with default label', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" select-all />');

    $view->assertSee('Select all');
});

it('renders select-all with custom label', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" select-all select-all-label="Marcar todos" />');

    $view->assertSee('Marcar todos');
    $view->assertDontSee('Select all');
});

it('marks individual checkboxes as checkbox targets only when select-all is active', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" select-all />');

    $view->assertSee('data-checkbox-select-all-target="checkbox"', false);

    $view2 = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" />');

    $view2->assertDontSee('data-checkbox-select-all-target', false);
});

// --- Class merge ---

it('merges custom class on wrapper', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" wrapper-class="space-y-2" />');

    $view->assertSee('class="space-y-2"', false);
});

it('does not render an empty class attribute when no classes are provided', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" />');

    $view->assertDontSee('class=""', false);
});

it('merges custom label-class on each label', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" label-class="font-bold" />');

    $view->assertSee('class="font-bold"', false);
});

it('merges custom label-class on the select-all master label too', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" select-all label-class="font-bold" />');

    $html = (string) $view;
    expect(substr_count($html, 'class="font-bold"'))->toBe(2);
});

it('emits the same checkable semantic state as <x-hw::input type=checkbox>', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" />');

    $view->assertSee('data-slot="checkbox-group-input"', false);
    $view->assertSee('data-checkable="true"', false);
    $view->assertDontSee('size-4', false);
    $view->assertDontSee('accent-primary', false);
});

it('emits semantic slots on the select-all master and item labels', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" select-all />');

    $html = (string) $view;
    expect(substr_count($html, 'data-slot="checkbox-group-input"'))->toBe(2)
        ->and(substr_count($html, 'data-slot="field-label"'))->toBe(2);
});

// --- User data-controller merge ---

it('merges user data-controller with checkbox-select-all when select-all', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" data-controller="foo" select-all />');

    $view->assertSee('data-controller="checkbox-select-all foo"', false);
});

it('merges inline stimulus attributes with checkbox-select-all', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" select-all :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'change\')" />');

    $view->assertSee('data-controller="checkbox-select-all analytics"', false);
    $view->assertSee('data-action="change->analytics#track"', false);
});

it('preserves user data-controller when no select-all', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" data-controller="foo" />');

    $view->assertSee('data-controller="foo"', false);
});

it('filters data-checkbox-select-all prefix when select-all is active', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" select-all data-checkbox-select-all-target="override" />');

    $view->assertDontSee('data-checkbox-select-all-target="override"', false);
});

it('adds auto-submit change action to option and rich item checkboxes', function () {
    $view = $this->blade('
        <x-hw::checkbox-group name="roles[]" :options="[\'admin\' => \'Admin\']" auto-submit>
            <x-hw::checkbox-group.item value="editor">Editor</x-hw::checkbox-group.item>
        </x-hw::checkbox-group>
    ');

    $html = (string) $view;
    expect(substr_count($html, 'data-action="change->auto-submit#submit"'))->toBe(2);
});

it('can force debounced auto-submit on option and rich item checkboxes', function () {
    $view = $this->blade('
        <x-hw::checkbox-group name="roles[]" :options="[\'admin\' => \'Admin\']" auto-submit="debounced" auto-submit-delay="600">
            <x-hw::checkbox-group.item value="editor">Editor</x-hw::checkbox-group.item>
        </x-hw::checkbox-group>
    ');

    $html = (string) $view;
    expect(substr_count($html, 'data-action="change->auto-submit#debouncedSubmit"'))->toBe(2)
        ->and(substr_count($html, 'data-auto-submit-delay-param="600"'))->toBe(2);
});

// --- Pass-through ---

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" data-test="x" hidden />');

    $view->assertSee('data-test="x"', false);
    $view->assertSee('hidden', false);
});

// --- Explicit name pass-through ---

it('renders name when explicitly provided inside field', function () {
    $view = $this->blade('
        <x-hw::field name="other[]">
            <x-hw::checkbox-group name="custom[]" :options="[1 => \'One\']" />
        </x-hw::field>
    ');

    $view->assertSee('name="custom[]"', false);
});

it('does not render name attribute when no name is provided', function () {
    $view = $this->blade('<x-hw::checkbox-group :options="[1 => \'One\']" />');

    $view->assertDontSee('name="', false);
});

it('field overrides name from @aware', function () {
    $view = $this->blade('
        <x-hw::field name="other[]">
            <x-hw::checkbox-group name="custom[]" :options="[1 => \'One\']" />
        </x-hw::field>
    ');

    $view->assertSee('name="custom[]"', false);
});

// --- Id derivation ---

it('generates unique ids per checkbox from name', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" />');

    $view->assertSee('id="ids-1"', false);
    $view->assertSee('id="ids-2"', false);
});

it('generates id from single name without brackets', function () {
    $view = $this->blade('<x-hw::checkbox-group name="active" :options="[1 => \'Active\']" />');

    $view->assertSee('id="active-1"', false);
});

it('uses explicit id as base for per-checkbox ids', function () {
    $view = $this->blade('<x-hw::checkbox-group name="branches[]" id="my-group" :options="[\'main\' => \'Main\', \'dev\' => \'Dev\']" />');

    $view->assertSee('id="my-group-main"', false);
    $view->assertSee('id="my-group-dev"', false);
});

it('derives rich item id from group name and value', function () {
    $view = $this->blade('
        <x-hw::checkbox-group name="roles[]">
            <x-hw::checkbox-group.item value="content editor">Editor</x-hw::checkbox-group.item>
        </x-hw::checkbox-group>
    ');

    $view->assertSee('id="roles-content-editor"', false);
});

it('does not set id when no name and no explicit id', function () {
    $view = $this->blade('<x-hw::checkbox-group :options="[1 => \'One\']" />');

    $view->assertDontSee('id="', false);
});

// --- ARIA ---

it('always sets aria-describedby on checkboxes', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\', 2 => \'Two\']" />');

    $view->assertSee('aria-describedby="ids-error"', false);
});

it('sets aria-invalid and data-invalid when error present', function () {
    shareCheckboxGroupErrors(['ids' => ['Required.']]);

    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" />');

    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('data-invalid', false);
});

it('does not set aria-invalid when no errors', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" />');

    $view->assertDontSee('aria-invalid="true"', false);
    $view->assertDontSee('data-invalid', false);
});

it('uses derived error key from bracket notation', function () {
    shareCheckboxGroupErrors(['variables.0.name' => ['Required.']]);

    $view = $this->blade('<x-hw::checkbox-group name="variables[0][name]" :options="[\'a\' => \'A\']" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('uses explicit error key override', function () {
    shareCheckboxGroupErrors(['custom.path' => ['Required.']]);

    $view = $this->blade('<x-hw::checkbox-group name="ids[]" error-key="custom.path" :options="[1 => \'One\']" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('applies validation state to rich item checkboxes', function () {
    shareCheckboxGroupErrors(['roles' => ['Required.']]);

    $view = $this->blade('
        <x-hw::checkbox-group name="roles[]">
            <x-hw::checkbox-group.item value="editor">Editor</x-hw::checkbox-group.item>
        </x-hw::checkbox-group>
    ');

    $view->assertSee('aria-describedby="roles-error"', false);
    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('data-invalid', false);
});

it('uses error key for error lookup, aria-describedby from name', function () {
    shareCheckboxGroupErrors(['custom' => ['Required.']]);

    $view = $this->blade('<x-hw::checkbox-group name="ids[]" error-key="custom" :options="[1 => \'One\']" />');

    // aria-describedby follows the name-derived id, not the error key
    $view->assertSee('aria-describedby="ids-error"', false);
    // Errors looked up on the explicit error key
    $view->assertSee('aria-invalid="true"', false);
    // error-key prop is consumed by component, not leaked as DOM attribute
    $view->assertDontSee('error-key', false);
});
