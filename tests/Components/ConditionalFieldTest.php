<?php

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

it('renders without hidden/disabled when current request matches the rule', function () {
    request()->merge(['reason' => 'other']);

    $view = $this->blade('<x-hwc::conditional-field :when="[\'reason\' => \'other\']">inside</x-hwc::conditional-field>');

    $view->assertDontSee(' hidden', false);
    $view->assertDontSee(' disabled', false);
});

it('renders hidden and disabled when the current request does not match', function () {
    request()->merge(['reason' => 'bug']);

    $view = $this->blade('<x-hwc::conditional-field :when="[\'reason\' => \'other\']">inside</x-hwc::conditional-field>');

    $view->assertSee('hidden', false);
    $view->assertSee('disabled', false);
});

it('renders hidden and disabled by default when the trigger field is absent', function () {
    $view = $this->blade('<x-hwc::conditional-field :when="[\'reason\' => \'other\']">inside</x-hwc::conditional-field>');

    $view->assertSee('hidden', false);
    $view->assertSee('disabled', false);
});

it('uses the state prop to override request input for edit forms', function () {
    request()->merge(['reason' => 'bug']);

    $view = $this->blade(
        '<x-hwc::conditional-field :when="[\'reason\' => \'other\']" :state="[\'reason\' => \'other\']">inside</x-hwc::conditional-field>'
    );

    $view->assertDontSee(' hidden', false);
    $view->assertDontSee(' disabled', false);
});

it('matches :checked token when the state value is truthy', function () {
    $view = $this->blade(
        '<x-hwc::conditional-field :when="[\'ship_different\' => \':checked\']" :state="[\'ship_different\' => \'1\']">x</x-hwc::conditional-field>'
    );

    $view->assertDontSee(' hidden', false);
});

it('matches :unchecked token when the state value is empty', function () {
    $view = $this->blade('<x-hwc::conditional-field :when="[\'agree\' => \':unchecked\']">x</x-hwc::conditional-field>');

    $view->assertDontSee(' hidden', false);
});

it('matches an array trigger value when the state contains the wanted value', function () {
    $view = $this->blade(
        '<x-hwc::conditional-field :when="[\'interests\' => \'events\']" :state="[\'interests\' => [\'news\', \'events\']]">x</x-hwc::conditional-field>'
    );

    $view->assertDontSee(' hidden', false);
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
