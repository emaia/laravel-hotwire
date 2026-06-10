<?php

use Emaia\LaravelHotwire\Components\ConditionalField;
use Illuminate\Database\Eloquent\Model;

class ConditionalFieldMessage extends Model
{
    protected $guarded = [];
}

function seedOldInput(array $values): void
{
    request()->setLaravelSession(session()->driver());
    session()->put('_old_input', $values);
}

it('renders a fieldset with the dependent target attribute', function () {
    $view = $this->blade('<x-hwc::conditional-field :when="[\'reason\' => \'other\']">inside</x-hwc::conditional-field>');

    $view->assertSee('<fieldset', false);
    $view->assertSee('data-conditional-fields-target="dependent"', false);
    $view->assertSee('inside');
});

it('emits a data-when-* attribute for a single string value', function () {
    $view = $this->blade('<x-hwc::conditional-field :when="[\'reason\' => \'other\']">inside</x-hwc::conditional-field>');

    $view->assertSee('data-when-reason="other"', false);
});

it('emits a space-separated data-when-* attribute for an array value', function () {
    $view = $this->blade('<x-hwc::conditional-field :when="[\'reason\' => [\'bug\', \'feature\']]">inside</x-hwc::conditional-field>');

    $view->assertSee('data-when-reason="bug feature"', false);
});

it('passes through tokens like :checked unchanged', function () {
    $view = $this->blade('<x-hwc::conditional-field :when="[\'ship_different\' => \':checked\']">inside</x-hwc::conditional-field>');

    $view->assertSee('data-when-ship_different=":checked"', false);
});

it('renders one attribute per field for multi-field AND rules', function () {
    $view = $this->blade(
        '<x-hwc::conditional-field :when="[\'authorized\' => \'no\', \'needs_visa\' => \'yes\']">inside</x-hwc::conditional-field>'
    );

    $view->assertSee('data-when-authorized="no"', false);
    $view->assertSee('data-when-needs_visa="yes"', false);
});

it('renders hidden and disabled by default when neither old() nor model is set', function () {
    $view = $this->blade('<x-hwc::conditional-field :when="[\'reason\' => \'other\']">inside</x-hwc::conditional-field>');

    $view->assertSee('hidden', false);
    $view->assertSee('disabled', false);
});

it('renders visible after validation retry — old() value drives the rule', function () {
    seedOldInput(['reason' => 'other']);

    $view = $this->blade('<x-hwc::conditional-field :when="[\'reason\' => \'other\']">inside</x-hwc::conditional-field>');

    $view->assertDontSee(' hidden', false);
    $view->assertDontSee(' disabled', false);
});

it('renders hidden after validation retry when old() value does not match', function () {
    seedOldInput(['reason' => 'bug']);

    $view = $this->blade('<x-hwc::conditional-field :when="[\'reason\' => \'other\']">inside</x-hwc::conditional-field>');

    $view->assertSee('hidden', false);
    $view->assertSee('disabled', false);
});

it('falls back to the model attribute when old() is empty', function () {
    $message = new ConditionalFieldMessage(['reason' => 'other']);

    $component = new ConditionalField(
        when: ['reason' => 'other'],
        model: $message,
    );

    expect($component->matches)->toBeTrue();
});

it('old() value wins over the model attribute on validation retry', function () {
    seedOldInput(['reason' => 'bug']);

    $message = new ConditionalFieldMessage(['reason' => 'other']);

    $component = new ConditionalField(
        when: ['reason' => 'other'],
        model: $message,
    );

    expect($component->matches)->toBeFalse();
});

it('matches :checked token when the model attribute is truthy', function () {
    $message = new ConditionalFieldMessage(['ship_different' => '1']);

    $component = new ConditionalField(
        when: ['ship_different' => ':checked'],
        model: $message,
    );

    expect($component->matches)->toBeTrue();
});

it('matches :unchecked token by default when no value is present', function () {
    $component = new ConditionalField(when: ['agree' => ':unchecked']);

    expect($component->matches)->toBeTrue();
});

it('matches an array trigger value when old() carries the wanted item', function () {
    seedOldInput(['interests' => ['news', 'events']]);

    $component = new ConditionalField(when: ['interests' => 'events']);

    expect($component->matches)->toBeTrue();
});

it('accepts a custom wrapper tag', function () {
    $view = $this->blade('<x-hwc::conditional-field :when="[\'reason\' => \'other\']" tag="div">x</x-hwc::conditional-field>');

    $view->assertSee('<div', false);
    $view->assertSee('data-conditional-fields-target="dependent"', false);
});

it('forwards extra attributes to the wrapper element', function () {
    $view = $this->blade('<x-hwc::conditional-field :when="[\'reason\' => \'other\']" class="my-class">x</x-hwc::conditional-field>');

    $view->assertSee('class="my-class"', false);
});
