<?php

use Emaia\LaravelHotwire\Components\CheckboxGroup;
use Emaia\LaravelHotwire\Components\CheckboxGroup\Item;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ViewException;

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

it('publishes only checkbox-group-scoped component data', function () {
    $groupData = (new CheckboxGroup(name: 'roles[]', selected: ['admin'], disabled: true))->data();
    $itemData = (new Item(value: 'admin', name: 'item-roles[]', disabled: false))->data();
    $frameworkKeys = ['componentName', 'attributes', 'ignoredParameterNames', 'internalPrefixes', 'compute'];

    $groupGenericKeys = array_values(array_filter(
        array_keys($groupData),
        fn (string $key) => ! str_starts_with($key, 'checkboxGroup') && ! str_starts_with($key, 'fieldOwner') && $key !== 'fieldControlContext' && ! in_array($key, $frameworkKeys, true),
    ));
    $itemGenericKeys = array_values(array_filter(
        array_keys($itemData),
        fn (string $key) => ! str_starts_with($key, 'checkboxGroupItem') && ! in_array($key, $frameworkKeys, true),
    ));

    expect($groupGenericKeys)->toBe([])
        ->and($itemGenericKeys)->toBe([])
        ->and($groupData)->toHaveKeys(['checkboxGroupContext', 'checkboxGroupName', 'checkboxGroupSelected', 'checkboxGroupDisabled'])
        ->and($groupData)->toHaveKey('fieldControlContext', null)
        ->and($itemData)->toHaveKeys(['checkboxGroupItemValue', 'checkboxGroupItemName', 'checkboxGroupItemDisabled']);
});

it('publishes a null field owner boundary when the checkbox group is nameless', function () {
    $data = (new CheckboxGroup)->data();

    expect($data)->toHaveKey('fieldOwner', false)
        ->and($data)->toHaveKey('fieldOwnerName', null)
        ->and($data)->toHaveKey('fieldOwnerId', null)
        ->and($data)->toHaveKey('fieldOwnerErrorKey', null);
});

it('keeps checkbox group context through an intermediate component', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $view = $this->blade(<<<'BLADE'
        <x-hw::checkbox-group name="roles[]" id="owner-checkbox" :selected="['admin']" select-all auto-submit="debounced" auto-submit-delay="700">
            <x-selection-context-wrapper name="shadow-group" id="shadow-group-id" error-key="shadow.group" :selected="[]" :old="false" disabled :select-all="false" type="single" variant="outline" size="lg" group-disabled :auto-submit="false" :auto-submit-delay="1">
                <x-hw::checkbox-group.item value="admin">Admin</x-hw::checkbox-group.item>
            </x-selection-context-wrapper>
        </x-hw::checkbox-group>
    BLADE);

    $view->assertSee('name="roles[]"', false)
        ->assertSee('id="owner-checkbox-admin"', false)
        ->assertSee('checked', false)
        ->assertSee('data-checkbox-select-all-target="checkbox"', false)
        ->assertSee('data-action="change->auto-submit#debouncedSubmit"', false)
        ->assertSee('data-auto-submit-delay-param="700"', false)
        ->assertDontSee('shadow-group', false)
        ->assertDontSee(' disabled', false);
});

it('keeps checkbox items bound to their family across another selection group', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::checkbox-group name="roles[]" :selected="['admin']">
            <x-hw::radio-group name="plan" selected="pro">
                <x-hw::checkbox-group.item value="admin">Admin</x-hw::checkbox-group.item>
            </x-hw::radio-group>
        </x-hw::checkbox-group>
    BLADE);

    $view->assertSee('name="roles[]"', false)
        ->assertSee('id="roles-admin"', false)
        ->assertSee('checked', false);
});

it('binds checkbox items to the nearest same-family root without leaking nullable context', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::checkbox-group name="outer-roles[]" :selected="['outer']">
            <x-hw::checkbox-group :selected="['inner']">
                <x-hw::checkbox-group.item value="inner">Inner</x-hw::checkbox-group.item>
            </x-hw::checkbox-group>
        </x-hw::checkbox-group>
    BLADE);

    $view->assertSee('checked', false)
        ->assertDontSee('name="outer-roles[]"', false)
        ->assertDontSee('id="outer-roles-inner"', false);
});

it('keeps explicit checkbox item identity ahead of group and field context', function () {
    shareCheckboxGroupErrors(['item.roles' => ['Choose a role.']]);

    $view = $this->blade(<<<'BLADE'
        <x-hw::field name="field-roles" id="field-checkbox" error-key="field.roles">
            <x-hw::checkbox-group name="group-roles[]" id="group-checkbox" error-key="group.roles">
                <x-hw::checkbox-group.item value="admin" name="item-roles[]" id="item-checkbox" error-key="item.roles">Admin</x-hw::checkbox-group.item>
            </x-hw::checkbox-group>
        </x-hw::field>
    BLADE);

    $view->assertSee('name="item-roles[]"', false)
        ->assertSee('id="item-checkbox-admin"', false)
        ->assertSee('aria-describedby="item-checkbox-error"', false)
        ->assertSee('aria-invalid="true"', false);
});

it('requires checkbox group items to render inside a checkbox group root', function () {
    $this->blade('<x-hw::checkbox-group.item value="admin" name="roles[]">Admin</x-hw::checkbox-group.item>');
})->throws(ViewException::class, 'must be rendered inside a Checkbox Group root');

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

it('exposes an explicit prop for disabling the select-all indeterminate state', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" select-all disable-indeterminate />');

    $view->assertSee('data-checkbox-select-all-disable-indeterminate-value="true"', false);
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
        ->and(substr_count($html, 'data-slot="checkbox-group-item"'))->toBe(2)
        ->and(substr_count($html, 'data-slot="checkbox-group-item-content"'))->toBe(2)
        ->and($html)->not->toContain('data-slot="field-label"');
});

it('renders orientation data attribute', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" orientation="horizontal" :options="[1 => \'One\']" />');

    $view->assertSee('data-orientation="horizontal"', false);
    $view->assertDontSee(' orientation="horizontal"', false);
});

it('falls back to vertical orientation for invalid values', function () {
    $view = $this->blade('<x-hw::checkbox-group name="ids[]" orientation="sideways" :options="[1 => \'One\']" />');

    $view->assertSee('data-orientation="vertical"', false);
});

it('disables all option and rich item checkboxes when disabled', function () {
    $view = $this->blade('
        <x-hw::checkbox-group name="ids[]" :options="[1 => \'One\']" disabled>
            <x-hw::checkbox-group.item value="2">Two</x-hw::checkbox-group.item>
        </x-hw::checkbox-group>
    ');

    expect(substr_count((string) $view, 'disabled'))->toBe(2);
});

it('allows a rich item to override the group disabled state', function () {
    $view = $this->blade('
        <x-hw::checkbox-group name="ids[]" disabled>
            <x-hw::checkbox-group.item value="1" :disabled="false">One</x-hw::checkbox-group.item>
        </x-hw::checkbox-group>
    ');

    expect((string) $view)->not->toContain('disabled');
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

it('resolves field error and label against the checkbox group name without a field root', function () {
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag(['roles' => ['Escolha uma opcao']]));
    view()->share('errors', $bag);

    $view = $this->blade(<<<'BLADE'
        <x-hw::checkbox-group name="roles[]" :options="['admin' => 'Admin']">
            <x-hw::field.label>Rotulo</x-hw::field.label>

            <x-hw::field.error />
        </x-hw::checkbox-group>
    BLADE);

    $html = (string) $view;

    expect($html)->toContain('id="roles-error"')
        ->toContain('Escolha uma opcao')
        ->toContain('aria-labelledby="roles-label"')
        ->toContain('id="roles-label"')
        ->not->toContain('hw-error-');
});

it('names a checkbox group with aria-labelledby instead of a dangling label for', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::checkbox-group name="roles[]" :options="['admin' => 'Admin']">
            <x-hw::field.label>Roles</x-hw::field.label>
        </x-hw::checkbox-group>
    BLADE);

    $view->assertSee('role="group"', false)
        ->assertSee('aria-labelledby="roles-label"', false)
        ->assertSee('id="roles-label"', false)
        ->assertDontSee('for="roles"', false);
});

it('ignores attributes ending in id when resolving the group label', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::checkbox-group name="tags" :options="['one' => 'One']">
            <x-hw::field.label data-tooltip-id="tip">Tags</x-hw::field.label>
        </x-hw::checkbox-group>
    BLADE);

    expect($html)->toContain('id="tags-label"')
        ->toContain('aria-labelledby="tags-label"')
        ->not->toContain('aria-labelledby="tip"');
});

it('names a checkbox group from a surrounding field label', function () {
    $html = (string) $this->blade('<x-hw::field name="roles[]" label="Roles" :error="false"><x-hw::checkbox-group :options="[\'admin\' => \'Admin\']" /></x-hw::field>');

    expect($html)->toMatch('/<div(?=[^>]*data-slot="checkbox-group")(?=[^>]*aria-labelledby="roles-label")[^>]*>/')
        ->toContain('id="roles-label"')
        ->not->toContain('for="roles"');
});
