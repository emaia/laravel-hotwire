<?php

use Emaia\LaravelHotwire\Components\Field;
use Illuminate\Support\Facades\Blade;
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

it('renders a wrapper div with field slot', function () {
    $view = $this->blade('<x-hw::field name="email"><span>x</span></x-hw::field>');

    $view->assertSee('data-slot="field"', false);
    $view->assertSee('role="group"', false);
    $view->assertSee('data-orientation="vertical"', false);
    $view->assertSee('<span>x</span>', false);
});

it('emits the requested orientation state', function () {
    $view = $this->blade('<x-hw::field name="email" orientation="horizontal"><span>x</span></x-hw::field>');

    $view->assertSee('data-orientation="horizontal"', false);
});

it('supports responsive orientation', function () {
    $view = $this->blade('<x-hw::field name="email" orientation="responsive"><span>x</span></x-hw::field>');

    $view->assertSee('data-orientation="responsive"', false);
});

it('merges custom class on wrapper', function () {
    $view = $this->blade('<x-hw::field name="email" class="space-y-1"><span>x</span></x-hw::field>');

    $view->assertSee('class="space-y-1"', false);
});

it('passes through arbitrary attributes on wrapper', function () {
    $view = $this->blade('<x-hw::field name="email" data-disabled data-invalid><span>x</span></x-hw::field>');

    $view->assertSee('data-disabled', false);
    $view->assertSee('data-invalid', false);
});

it('emits disabled and invalid data state props', function () {
    $view = $this->blade('<x-hw::field name="email" disabled invalid><span>x</span></x-hw::field>');

    $view->assertSee('data-disabled="true"', false);
    $view->assertSee('data-invalid="true"', false);
});

it('does not auto-render label or description', function () {
    $view = $this->blade('<x-hw::field name="email"><span>x</span></x-hw::field>');

    $view->assertDontSee('<label', false);
    $view->assertDontSee('data-slot="field-description"', false);
});

// --- Auto-rendered error ---

it('auto-renders <x-hw::field.error> at the end when name is set', function () {
    $view = $this->blade('<x-hw::field name="email"><span>x</span></x-hw::field>');

    $view->assertSee('id="email-error"', false);
    $view->assertSee('role="alert"', false);
});

it('auto-renders error with the field error key when name has bracket notation', function () {
    $view = $this->blade('<x-hw::field name="variables[0][name]"><span>x</span></x-hw::field>');

    $view->assertSee('id="variables-0-name-error"', false);
});

it('auto-renders error showing the validation message for the field name', function () {
    shareFieldErrors(['email' => ['Required']]);

    $view = $this->blade('<x-hw::field name="email"><span>x</span></x-hw::field>');

    $view->assertSee('Required');
});

it('does not auto-render error when name is not set', function () {
    $view = $this->blade('<x-hw::field><span>x</span></x-hw::field>');

    $view->assertDontSee('role="alert"', false);
});

it('does not auto-render error when :error="false"', function () {
    $view = $this->blade('<x-hw::field name="email" :error="false"><span>x</span></x-hw::field>');

    $view->assertDontSee('role="alert"', false);
});

it('does not duplicate the error node when slot already includes one', function () {
    $view = $this->blade('
        <x-hw::field name="email" :error="false">
            <x-hw::input type="email" />
            <x-hw::field.error class="custom" />
        </x-hw::field>
    ');

    expect(substr_count((string) $view, 'id="email-error"'))->toBe(1);
});

it('auto-rendered error uses field error-key override', function () {
    shareFieldErrors(['indicator.name' => ['Required']]);

    $view = $this->blade('
        <x-hw::field name="variables[0][name]" error-key="indicator.name">
            <x-hw::input type="text" />
        </x-hw::field>
    ');

    $view->assertSee('Required');
});

// --- Auto-rendered label ---

it('auto-renders label before slot when label prop is provided', function () {
    $view = $this->blade('
        <x-hw::field name="email" label="E-mail">
            <x-hw::input type="email" />
        </x-hw::field>
    ');

    $view->assertSee('<label', false);
    $view->assertSee('E-mail');
    $view->assertSee('for="email"', false);
});

it('auto-rendered label uses required-label prop', function () {
    $view = $this->blade('
        <x-hw::field name="email" label="E-mail" required required-label="(obrigatório)">
            <x-hw::input type="email" />
        </x-hw::field>
    ');

    $view->assertSee('(obrigatório)');
    $view->assertDontSee('*', false);
});

it('auto-rendered label shows default asterisk when required', function () {
    $view = $this->blade('
        <x-hw::field name="email" label="E-mail" required>
            <x-hw::input type="email" />
        </x-hw::field>
    ');

    $html = (string) $view;
    // The label contains '*' but rendered via component, not as raw '*'
    expect($html)->toContain('<span data-slot="field-label-required"');
});

it('does not auto-render label when label prop is null', function () {
    $view = $this->blade('
        <x-hw::field name="email">
            <x-hw::input type="email" />
        </x-hw::field>
    ');

    $view->assertDontSee('<label', false);
});

it('does not auto-render label when label prop is empty string', function () {
    $view = $this->blade('
        <x-hw::field name="email" label="">
            <x-hw::input type="email" />
        </x-hw::field>
    ');

    $view->assertDontSee('<label', false);
});

it('auto-rendered label coexists with auto-rendered error', function () {
    $view = $this->blade('
        <x-hw::field name="email" label="E-mail">
            <x-hw::input type="email" />
        </x-hw::field>
    ');

    $view->assertSee('for="email"', false);
    $view->assertSee('id="email-error"', false);
});

it('auto-rendered label appears before slot content', function () {
    $view = $this->blade('
        <x-hw::field name="email" label="E-mail">
            <x-hw::input type="email" value="test" />
        </x-hw::field>
    ');

    $html = (string) $view;
    $labelPos = strpos($html, '<label');
    $inputPos = strpos($html, 'value="test"');
    expect($labelPos)->toBeLessThan($inputPos);
});

// --- Auto-rendered description ---

it('auto-renders description between slot and error when description prop is provided', function () {
    $view = $this->blade('
        <x-hw::field name="email" description="Enter your work email address.">
            <x-hw::input type="email" />
        </x-hw::field>
    ');

    $view->assertSee('data-slot="field-description"', false);
    $view->assertSee('Enter your work email address.');
});

it('does not auto-render description when description prop is null', function () {
    $view = $this->blade('
        <x-hw::field name="email">
            <x-hw::input type="email" />
        </x-hw::field>
    ');

    $view->assertDontSee('data-slot="field-description"', false);
});

it('does not auto-render description when description prop is empty string', function () {
    $view = $this->blade('
        <x-hw::field name="email" description="">
            <x-hw::input type="email" />
        </x-hw::field>
    ');

    $view->assertDontSee('data-slot="field-description"', false);
});

it('auto-rendered description coexists with auto-rendered label', function () {
    $view = $this->blade('
        <x-hw::field name="email" label="E-mail" description="We will never share your email.">
            <x-hw::input type="email" />
        </x-hw::field>
    ');

    $view->assertSee('<label', false);
    $view->assertSee('E-mail');
    $view->assertSee('data-slot="field-description"', false);
    $view->assertSee('We will never share your email.');
});

it('auto-rendered description appears after slot and before error', function () {
    $view = $this->blade('
        <x-hw::field name="email" description="Helper text.">
            <x-hw::input type="email" value="test" />
        </x-hw::field>
    ');

    $html = (string) $view;
    $inputPos = strpos($html, 'value="test"');
    $descPos = strpos($html, 'data-slot="field-description"');
    $errorPos = strpos($html, 'id="email-error"');
    expect($inputPos)->toBeLessThan($descPos);
    expect($descPos)->toBeLessThan($errorPos);
});

// --- @aware propagation ---

it('publishes only field-scoped component data', function () {
    $data = (new Field(
        name: 'email',
        id: 'email-control',
        label: 'Email',
        description: 'Work address',
        requiredLabel: 'Required',
        errorKey: 'profile.email',
        required: true,
        error: false,
        orientation: 'horizontal',
        class: 'field-class',
        wrapperId: 'email-field',
        disabled: true,
        invalid: true,
    ))->data();
    $frameworkKeys = ['componentName', 'attributes', 'ignoredParameterNames'];
    $genericKeys = array_values(array_filter(
        array_keys($data),
        fn (string $key) => ! str_starts_with($key, 'field') && ! in_array($key, $frameworkKeys, true),
    ));

    expect($genericKeys)->toBe([])
        ->and($data)->toHaveKeys([
            'fieldName',
            'fieldId',
            'fieldLabel',
            'fieldDescription',
            'fieldRequiredLabel',
            'fieldErrorKey',
            'fieldRequired',
            'fieldError',
            'fieldOrientation',
            'fieldClass',
            'fieldWrapperId',
            'fieldDisabled',
            'fieldInvalid',
        ]);
});

it('uses id for the control context and wrapper-id for the field container', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::field name="email" id="email-control" wrapper-id="email-field" label="Email">
            <x-hw::input />
        </x-hw::field>
    BLADE);

    $view->assertSee('id="email-field"', false)
        ->assertSee('id="email-control"', false)
        ->assertSee('for="email-control"', false)
        ->assertSee('aria-describedby="email-control-error"', false)
        ->assertSee('id="email-control-error"', false);
});

it('keeps field context through an intermediate component with generic props', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');
    shareFieldErrors(['profile.email' => ['Required']]);

    $view = $this->blade(<<<'BLADE'
        <x-hw::field name="email" id="email-control" error-key="profile.email" required>
            <x-field-context-wrapper name="shadow-field" id="shadow-field-id" error-key="shadow.field" :required="false">
                <x-hw::field.label>Email</x-hw::field.label>
                <x-hw::input />
                <x-hw::field.error />
            </x-field-context-wrapper>
        </x-hw::field>
    BLADE);

    $view->assertSee('name="email"', false)
        ->assertSee('id="email-control"', false)
        ->assertSee('for="email-control"', false)
        ->assertSee('id="email-control-error"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-required="true"', false)
        ->assertDontSee('shadow-field', false)
        ->assertDontSee('shadow-field-id', false);
});

it('keeps explicit control props ahead of field context', function () {
    shareFieldErrors(['child.email' => ['Invalid']]);

    $view = $this->blade(<<<'BLADE'
        <x-hw::field name="email" id="email-control" error-key="profile.email" required :error="false">
            <x-hw::input name="alternate" id="alternate-control" error-key="child.email" :required="false" />
            <x-hw::field.error name="alternate" id="alternate-control-error" error-key="child.email" />
        </x-hw::field>
    BLADE);

    $view->assertSee('name="alternate"', false)
        ->assertSee('id="alternate-control"', false)
        ->assertSee('aria-describedby="alternate-control-error"', false)
        ->assertSee('id="alternate-control-error"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertDontSee('aria-required="true"', false)
        ->assertDontSee(' required', false);
});

it('keeps scoped field identity across every field-aware control', function (string $component, string $identity) {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $view = $this->blade(<<<BLADE
        <x-hw::field name="profile[email]" id="owner-control" :error="false">
            <x-field-context-wrapper name="shadow-field" id="shadow-field-id" error-key="shadow.field" :required="false">
                {$component}
            </x-field-context-wrapper>
        </x-hw::field>
    BLADE);

    $view->assertSee('profile[email]', false)
        ->assertSee($identity, false)
        ->assertDontSee('shadow-field', false)
        ->assertDontSee('shadow-field-id', false);
})->with([
    'input' => ['<x-hw::input />', 'id="owner-control"'],
    'select' => ['<x-hw::select><option value="x">X</option></x-hw::select>', 'id="owner-control"'],
    'checkbox' => ['<x-hw::checkbox />', 'id="owner-control"'],
    'switch' => ['<x-hw::switch />', 'id="owner-control"'],
    'textarea' => ['<x-hw::textarea />', 'id="owner-control"'],
    'file' => ['<x-hw::file />', 'id="owner-control"'],
    'file upload' => ['<x-hw::file-upload url="/uploads" />', 'id="owner-control"'],
    'multi select' => ['<x-hw::multi-select :options="[\'active\' => \'Active\']" />', 'id="owner-control"'],
    'rich text' => ['<x-hw::rich-text />', 'data-rich-text-id-value="owner-control"'],
    'slider' => ['<x-hw::slider />', 'id="owner-control"'],
    'radio group' => ['<x-hw::radio-group :options="[\'free\' => \'Free\']" />', 'id="owner-control-free"'],
    'checkbox group' => ['<x-hw::checkbox-group :options="[\'admin\' => \'Admin\']" />', 'id="owner-control-admin"'],
    'toggle group' => ['<x-hw::toggle-group><x-hw::toggle-group.item value="bold">Bold</x-hw::toggle-group.item></x-hw::toggle-group>', 'id="owner-control-bold-input"'],
]);

it('propagates name to nested input', function () {
    $view = $this->blade('
        <x-hw::field name="email">
            <x-hw::input type="email" />
        </x-hw::field>
    ');

    $view->assertSee('id="email"', false);
    $view->assertSee('name="email"', false);
    $view->assertSee('aria-describedby="email-error"', false);
});

it('propagates name to nested label and error', function () {
    $view = $this->blade('
        <x-hw::field name="email">
            <x-hw::field.label>E-mail</x-hw::field.label>
            <x-hw::field.error />
        </x-hw::field>
    ');

    $view->assertSee('for="email"', false);
    $view->assertSee('id="email-error"', false);
});

it('propagates name with bracket notation to nested children', function () {
    $view = $this->blade('
        <x-hw::field name="variables[0][name]">
            <x-hw::field.label>Variables</x-hw::field.label>
            <x-hw::input type="text" />
            <x-hw::field.error />
        </x-hw::field>
    ');

    $view->assertSee('name="variables[0][name]"', false);
    $view->assertSee('id="variables-0-name"', false);
    $view->assertSee('for="variables-0-name"', false);
    $view->assertSee('id="variables-0-name-error"', false);
});

it('propagates required to nested label and input', function () {
    $view = $this->blade('
        <x-hw::field name="email" required>
            <x-hw::field.label>E-mail</x-hw::field.label>
            <x-hw::input type="email" />
        </x-hw::field>
    ');

    $view->assertSee('required', false);
    $view->assertSee('aria-required="true"', false);
});

// --- Override errorKey from field ---

it('overrides errorKey when explicit error-key is set on field', function () {
    shareFieldErrors(['indicator.name' => ['Required']]);

    $view = $this->blade('
        <x-hw::field name="variables[0][name]" error-key="indicator.name">
            <x-hw::input type="text" />
            <x-hw::field.error />
        </x-hw::field>
    ');

    $view->assertSee('Required');
    $view->assertSee('aria-invalid="true"', false);
});

it('keeps a label for on a field that wraps a single control', function () {
    $view = $this->blade('<x-hw::field name="email" label="Email" :error="false"><x-hw::input /></x-hw::field>');

    $view->assertSee('for="email"', false)
        ->assertDontSee('aria-labelledby', false);
});

it('resets a selection owner at a nested field boundary', function () {
    shareFieldErrors(['outer.key' => ['Outer message'], 'inner' => ['Inner message']]);

    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::radio-group name="outer" id="outer-id" error-key="outer.key">
            <x-hw::field name="inner" label="Inner">
                <x-hw::input />
            </x-hw::field>
        </x-hw::radio-group>
    BLADE);

    expect($html)->toContain('for="inner"')
        ->toContain('id="inner"')
        ->toContain('id="inner-error"')
        ->toContain('Inner message')
        ->not->toContain('for="outer-id"')
        ->not->toContain('id="outer-id-error"')
        ->not->toContain('Outer message');
});

it('uses a named selection group to identify a label from a nameless field', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::field label="Tags" :error="false">
            <x-hw::checkbox-group name="tags" :options="['one' => 'One']" />
        </x-hw::field>
    BLADE);

    expect($html)->toContain('id="tags-label"')
        ->toMatch('/<div(?=[^>]*data-slot="checkbox-group")(?=[^>]*aria-labelledby="tags-label")[^>]*>/');
});

it('lets an inner selection label suppress the outer automatic label', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::field name="plan" label="Plan" :error="false">
            <x-hw::radio-group>
                <x-hw::field.label>Choose</x-hw::field.label>
                <x-hw::radio-group.item value="free">Free</x-hw::radio-group.item>
            </x-hw::radio-group>
        </x-hw::field>
    BLADE);

    expect(substr_count($html, 'id="plan-label"'))->toBe(1)
        ->and($html)->toContain('Choose')
        ->and($html)->not->toContain('>Plan<');
});

it('assigns set ownership only to the nested selection group', function () {
    $html = (string) $this->blade('<x-hw::field name="plan" label="Plan" :error="false"><x-hw::radio-group :options="[\'free\' => \'Free\']" /></x-hw::field>');

    expect($html)->toMatch('/<div(?=[^>]*data-slot="radio-group")(?=[^>]*role="radiogroup")(?=[^>]*aria-labelledby="plan-label")[^>]*>/')
        ->toMatch('/<div(?=[^>]*data-slot="field")(?![^>]*role=)(?![^>]*aria-labelledby=)[^>]*>/');
});

it('supports explicit set semantics for raw custom controls', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::field label="Choices" set="radiogroup" label-id="choices-label" :error="false">
            <input type="radio" name="choice" value="a">
            <input type="radio" name="choice" value="b">
        </x-hw::field>
    BLADE);

    expect($html)->toMatch('/<div(?=[^>]*data-slot="field")(?=[^>]*role="radiogroup")(?=[^>]*aria-labelledby="choices-label")[^>]*>/')
        ->toContain('id="choices-label"')
        ->not->toContain(' for=');
});

it('rejects unsupported explicit set semantics', function () {
    expect(fn () => new Field(set: 'listbox'))
        ->toThrow(InvalidArgumentException::class, 'The Field set prop must be group, radiogroup, or null.');
});

it('keeps the automatic label and its idref when a field contains multiple selection groups', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::field label="Preferences" :error="false">
            <x-hw::radio-group name="plan" :options="['free' => 'Free']" />
            <x-hw::checkbox-group name="topics" :options="['news' => 'News']" />
        </x-hw::field>
    BLADE);

    expect($html)->toContain('Preferences')
        ->toContain('id="plan-label"')
        ->toContain('aria-labelledby="plan-label"');
});

it('keeps the automatic label and its idref when a field contains a selection group and a control', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::field label="Preferences" :error="false">
            <x-hw::radio-group name="plan" :options="['free' => 'Free']" />
            <x-hw::input name="notes" />
        </x-hw::field>
    BLADE);

    expect($html)->toContain('Preferences')
        ->toContain('id="plan-label"')
        ->toContain('aria-labelledby="plan-label"');
});

it('lets only the outer selection group consume a field automatic label', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::field label="Preferences" :error="false">
            <x-hw::checkbox-group name="topics">
                <x-hw::radio-group name="plan" :options="['free' => 'Free']" />
            </x-hw::checkbox-group>
        </x-hw::field>
    BLADE);

    expect($html)->toContain('Preferences')
        ->toContain('id="topics-label"')
        ->toMatch('/<div(?=[^>]*data-slot="checkbox-group")(?=[^>]*aria-labelledby="topics-label")[^>]*>/')
        ->toMatch('/<div(?=[^>]*data-slot="radio-group")(?![^>]*aria-labelledby)[^>]*>/')
        ->not->toContain('aria-labelledby="plan-label"');
});

it('keeps a visible field label when a selection group supplies aria-label', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::field name="plan" label="Visible plan" :error="false">
            <x-hw::radio-group aria-label="Plan" :options="['free' => 'Free']" />
        </x-hw::field>
    BLADE);

    expect($html)->toContain('Visible plan')
        ->toContain('aria-label="Plan"');
});

it('honors an external label id for an explicit set without automatic label text', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <span id="external-label">Choices</span>
        <x-hw::field set="radiogroup" label-id="external-label" :error="false">
            <input type="radio" name="choice" value="a">
        </x-hw::field>
    BLADE);

    expect($html)->toMatch('/<div(?=[^>]*data-slot="field")(?=[^>]*role="radiogroup")(?=[^>]*aria-labelledby="external-label")[^>]*>/');
});
