<?php

use Emaia\LaravelHotwire\Components\RadioGroup;
use Emaia\LaravelHotwire\Components\RadioGroup\Item;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ViewException;

function shareRadioGroupErrors(array $errorsByKey): void
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

it('renders radio inputs from options', function () {
    $view = $this->blade('<x-hw::radio-group name="visibility" :options="[\'public\' => \'Public\', \'private\' => \'Private\']" />');

    $view->assertSee('data-slot="radio-group"', false);
    $view->assertSee('type="radio"', false);
    $view->assertSee('name="visibility"', false);
    $view->assertSee('value="public"', false);
    $view->assertSee('Public');
    $view->assertSee('value="private"', false);
    $view->assertSee('Private');
});

it('normalizes flat options array so keys equal values', function () {
    $view = $this->blade('<x-hw::radio-group name="plan" :options="[\'free\', \'pro\']" />');

    $view->assertSee('value="free"', false);
    $view->assertSee('value="pro"', false);
});

it('renders rich item slot content alongside option radios', function () {
    $view = $this->blade('
        <x-hw::radio-group name="plan" :options="[\'free\' => \'Free\']">
            <x-hw::radio-group.item value="pro">
                <span data-test="title">Pro</span>
                <span data-test="description">For teams.</span>
            </x-hw::radio-group.item>
        </x-hw::radio-group>
    ');

    $html = (string) $view;
    expect($html)->toContain('value="free"')
        ->and($html)->toContain('Free')
        ->and($html)->toContain('value="pro"')
        ->and($html)->toContain('data-test="title"')
        ->and($html)->toContain('For teams.');
});

// --- Selected / old ---

it('checks the selected value', function () {
    $view = $this->blade('<x-hw::radio-group name="plan" :options="[\'free\' => \'Free\', \'pro\' => \'Pro\']" selected="pro" />');

    $html = (string) $view;
    expect($html)->toContain('value="pro"')
        ->and($html)->toContain('checked')
        ->and(substr_count($html, 'checked'))->toBe(1);
});

it('checks rich item values from selected value', function () {
    $view = $this->blade('
        <x-hw::radio-group name="plan" selected="pro">
            <x-hw::radio-group.item value="free">Free</x-hw::radio-group.item>
            <x-hw::radio-group.item value="pro">Pro</x-hw::radio-group.item>
        </x-hw::radio-group>
    ');

    $view->assertSee('value="pro"', false);
    expect(substr_count((string) $view, 'checked'))->toBe(1);
});

it('does not check anything when selected is null', function () {
    $view = $this->blade('<x-hw::radio-group name="plan" :options="[\'free\' => \'Free\', \'pro\' => \'Pro\']" />');

    $view->assertDontSee('checked', false);
});

it('restores selected value from old input', function () {
    session()->put('_old_input', ['plan' => 'pro']);

    $view = $this->blade('<x-hw::radio-group name="plan" :options="[\'free\' => \'Free\', \'pro\' => \'Pro\']" selected="free" />');

    $html = (string) $view;
    expect($html)->toContain('value="pro"')
        ->and($html)->toContain('checked')
        ->and(substr_count($html, 'checked'))->toBe(1);
});

it('disables old input restore when old is false', function () {
    session()->put('_old_input', ['plan' => 'pro']);

    $view = $this->blade('<x-hw::radio-group name="plan" :options="[\'free\' => \'Free\', \'pro\' => \'Pro\']" selected="free" :old="false" />');

    $html = (string) $view;
    expect($html)->toContain('value="free"')
        ->and($html)->toContain('checked')
        ->and(substr_count($html, 'checked'))->toBe(1);
});

it('restores rich item checked state from old input', function () {
    session()->put('_old_input', ['plan' => 'pro']);

    $view = $this->blade('
        <x-hw::radio-group name="plan" selected="free">
            <x-hw::radio-group.item value="free">Free</x-hw::radio-group.item>
            <x-hw::radio-group.item value="pro">Pro</x-hw::radio-group.item>
        </x-hw::radio-group>
    ');

    expect(substr_count((string) $view, 'checked'))->toBe(1);
});

// --- Field awareness ---

it('publishes only radio-group-scoped component data', function () {
    $groupData = (new RadioGroup(name: 'plan', selected: 'pro', disabled: true))->data();
    $itemData = (new Item(value: 'pro', name: 'item-plan', disabled: false))->data();
    $frameworkKeys = ['componentName', 'attributes', 'ignoredParameterNames', 'internalPrefixes', 'compute'];

    $groupGenericKeys = array_values(array_filter(
        array_keys($groupData),
        fn (string $key) => ! str_starts_with($key, 'radioGroup') && ! str_starts_with($key, 'fieldOwner') && ! in_array($key, $frameworkKeys, true),
    ));
    $itemGenericKeys = array_values(array_filter(
        array_keys($itemData),
        fn (string $key) => ! str_starts_with($key, 'radioGroupItem') && ! in_array($key, $frameworkKeys, true),
    ));

    expect($groupGenericKeys)->toBe([])
        ->and($itemGenericKeys)->toBe([])
        ->and($groupData)->toHaveKeys(['radioGroupContext', 'radioGroupName', 'radioGroupSelected', 'radioGroupDisabled'])
        ->and($itemData)->toHaveKeys(['radioGroupItemValue', 'radioGroupItemName', 'radioGroupItemDisabled']);
});

it('keeps radio group context through an intermediate component', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $view = $this->blade(<<<'BLADE'
        <x-hw::radio-group name="plan" id="owner-radio" selected="pro" auto-submit="debounced" auto-submit-delay="700">
            <x-selection-context-wrapper>
                <x-hw::radio-group.item value="pro">Pro</x-hw::radio-group.item>
            </x-selection-context-wrapper>
        </x-hw::radio-group>
    BLADE);

    $view->assertSee('name="plan"', false)
        ->assertSee('id="owner-radio-pro"', false)
        ->assertSee('checked', false)
        ->assertSee('data-action="change->auto-submit#debouncedSubmit"', false)
        ->assertSee('data-auto-submit-delay-param="700"', false)
        ->assertDontSee('shadow-group', false)
        ->assertDontSee(' disabled', false);
});

it('keeps radio items bound to their family across another selection group', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::radio-group name="plan" selected="pro">
            <x-hw::checkbox-group name="roles[]" :selected="['admin']">
                <x-hw::radio-group.item value="pro">Pro</x-hw::radio-group.item>
            </x-hw::checkbox-group>
        </x-hw::radio-group>
    BLADE);

    $view->assertSee('name="plan"', false)
        ->assertSee('id="plan-pro"', false)
        ->assertSee('checked', false);
});

it('binds radio items to the nearest same-family root without leaking nullable context', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::radio-group name="outer-plan" selected="outer">
            <x-hw::radio-group selected="inner">
                <x-hw::radio-group.item value="inner">Inner</x-hw::radio-group.item>
            </x-hw::radio-group>
        </x-hw::radio-group>
    BLADE);

    $view->assertSee('checked', false)
        ->assertDontSee('name="outer-plan"', false)
        ->assertDontSee('id="outer-plan-inner"', false);
});

it('keeps explicit radio item identity ahead of group and field context', function () {
    shareRadioGroupErrors(['item.plan' => ['Choose a plan.']]);

    $view = $this->blade(<<<'BLADE'
        <x-hw::field name="field-plan" id="field-radio" error-key="field.plan">
            <x-hw::radio-group name="group-plan" id="group-radio" error-key="group.plan">
                <x-hw::radio-group.item value="pro" name="item-plan" id="item-radio" error-key="item.plan">Pro</x-hw::radio-group.item>
            </x-hw::radio-group>
        </x-hw::field>
    BLADE);

    $view->assertSee('name="item-plan"', false)
        ->assertSee('id="item-radio-pro"', false)
        ->assertSee('aria-describedby="item-radio-error"', false)
        ->assertSee('aria-invalid="true"', false);
});

it('requires radio group items to render inside a radio group root', function () {
    $this->blade('<x-hw::radio-group.item value="pro" name="plan">Pro</x-hw::radio-group.item>');
})->throws(ViewException::class, 'must be rendered inside a Radio Group root');

it('inherits name from field wrapper', function () {
    $view = $this->blade('
        <x-hw::field name="plan">
            <x-hw::radio-group :options="[\'free\' => \'Free\']" />
        </x-hw::field>
    ');

    $view->assertSee('name="plan"', false);
});

it('explicit group name overrides field name', function () {
    $view = $this->blade('
        <x-hw::field name="plan">
            <x-hw::radio-group name="billing_plan" :options="[\'free\' => \'Free\']" />
        </x-hw::field>
    ');

    $view->assertSee('name="billing_plan"', false);
    $view->assertDontSee('name="plan"', false);
});

it('does not render name attribute when no name is provided', function () {
    $view = $this->blade('<x-hw::radio-group :options="[\'free\' => \'Free\']" />');

    $view->assertDontSee('name="', false);
});

// --- Id / ARIA / validation ---

it('generates unique ids per radio from name and value', function () {
    $view = $this->blade('<x-hw::radio-group name="plan" :options="[\'free\' => \'Free\', \'team plan\' => \'Team\']" />');

    $view->assertSee('id="plan-free"', false);
    $view->assertSee('id="plan-team-plan"', false);
});

it('uses explicit id as base for per-radio ids', function () {
    $view = $this->blade('<x-hw::radio-group name="plan" id="billing" :options="[\'free\' => \'Free\']" />');

    $view->assertSee('id="billing-free"', false);
});

it('derives rich item id from group name and value', function () {
    $view = $this->blade('
        <x-hw::radio-group name="plan">
            <x-hw::radio-group.item value="team plan">Team</x-hw::radio-group.item>
        </x-hw::radio-group>
    ');

    $view->assertSee('id="plan-team-plan"', false);
});

it('always sets aria-describedby on radios', function () {
    $view = $this->blade('<x-hw::radio-group name="plan" :options="[\'free\' => \'Free\']" />');

    $view->assertSee('aria-describedby="plan-error"', false);
});

it('sets aria-invalid and data-invalid when error present', function () {
    shareRadioGroupErrors(['plan' => ['Required.']]);

    $view = $this->blade('<x-hw::radio-group name="plan" :options="[\'free\' => \'Free\']" />');

    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('data-invalid', false);
});

it('uses explicit error key override', function () {
    shareRadioGroupErrors(['custom.path' => ['Required.']]);

    $view = $this->blade('<x-hw::radio-group name="plan" error-key="custom.path" :options="[\'free\' => \'Free\']" />');

    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('aria-describedby="plan-error"', false);
    $view->assertDontSee('error-key', false);
});

// --- Disabled / orientation / classes ---

it('disables all option and rich item radios when disabled', function () {
    $view = $this->blade('
        <x-hw::radio-group name="plan" :options="[\'free\' => \'Free\']" disabled>
            <x-hw::radio-group.item value="pro">Pro</x-hw::radio-group.item>
        </x-hw::radio-group>
    ');

    expect(substr_count((string) $view, 'disabled'))->toBe(2);
});

it('renders orientation data attribute', function () {
    $view = $this->blade('<x-hw::radio-group name="plan" orientation="horizontal" :options="[\'free\' => \'Free\']" />');

    $view->assertSee('data-orientation="horizontal"', false);
    $view->assertDontSee(' orientation="horizontal"', false);
});

it('merges wrapper and label classes', function () {
    $view = $this->blade('<x-hw::radio-group name="plan" wrapper-class="gap-4" label-class="font-bold" :options="[\'free\' => \'Free\']" />');

    $view->assertSee('class="gap-4"', false);
    $view->assertSee('class="font-bold"', false);
});

// --- Auto submit / pass-through ---

it('adds auto-submit change action to option and rich item radios', function () {
    $view = $this->blade('
        <x-hw::radio-group name="plan" :options="[\'free\' => \'Free\']" auto-submit>
            <x-hw::radio-group.item value="pro">Pro</x-hw::radio-group.item>
        </x-hw::radio-group>
    ');

    expect(substr_count((string) $view, 'data-action="change->auto-submit#submit"'))->toBe(2);
});

it('can force debounced auto-submit on option and rich item radios', function () {
    $view = $this->blade('
        <x-hw::radio-group name="plan" :options="[\'free\' => \'Free\']" auto-submit="debounced" auto-submit-delay="600">
            <x-hw::radio-group.item value="pro">Pro</x-hw::radio-group.item>
        </x-hw::radio-group>
    ');

    $html = (string) $view;
    expect(substr_count($html, 'data-action="change->auto-submit#debouncedSubmit"'))->toBe(2)
        ->and(substr_count($html, 'data-auto-submit-delay-param="600"'))->toBe(2);
});

it('passes through arbitrary wrapper attributes', function () {
    $view = $this->blade('<x-hw::radio-group name="plan" :options="[\'free\' => \'Free\']" data-test="x" hidden />');

    $view->assertSee('data-test="x"', false);
    $view->assertSee('hidden', false);
});

// --- Catalog ---

it('registers radio group in the component catalog', function () {
    $registry = HotwireRegistry::make();

    $radioGroup = $registry->component('radio-group');
    $radioItem = $registry->component('radio-group.item');

    expect($radioGroup->class)->toBe(RadioGroup::class)
        ->and($radioGroup->view)->toBe('hotwire::component-views.radio-group')
        ->and($radioGroup->controllers)->toBe(['auto-submit'])
        ->and($radioItem->class)->toBe(Item::class)
        ->and($radioItem->view)->toBe('hotwire::component-views.radio-group-item')
        ->and($radioItem->controllers)->toBe(['auto-submit']);
});

it('resolves field error and label against the radio group name without a field root', function () {
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag(['plan' => ['Escolha uma opcao']]));
    view()->share('errors', $bag);

    $view = $this->blade(<<<'BLADE'
        <x-hw::radio-group name="plan" :options="['free' => 'Free']">
            <x-hw::field.label>Rotulo</x-hw::field.label>
            
            <x-hw::field.error />
        </x-hw::radio-group>
    BLADE);

    $html = (string) $view;

    expect($html)->toContain('id="plan-error"')
        ->toContain('Escolha uma opcao')
        ->toContain('for="plan"')
        ->not->toContain('hw-error-');
});
