<?php

beforeEach(function () {
    view()->share('errors', new \Illuminate\Support\ViewErrorBag);
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
