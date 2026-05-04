<?php

beforeEach(function () {
    view()->share('errors', new \Illuminate\Support\ViewErrorBag);
});

// --- Plain render ---

it('renders the combobox wrapper with data-controller', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" />');

    $view->assertSee('data-controller="combobox"', false);
});

it('renders the trigger button', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" />');

    $view->assertSee('<button', false);
    $view->assertSee('data-combobox-target="trigger"', false);
    $view->assertSee('aria-haspopup="listbox"', false);
});

it('renders the hidden input with name and value', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" value="apple" />');

    $view->assertSee('type="hidden"', false);
    $view->assertSee('name="fruit"', false);
    $view->assertSee('value="apple"', false);
});

it('renders options as role="option" divs', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\', \'banana\' => \'Banana\']" />');

    $view->assertSee('role="option"', false);
    $view->assertSee('data-value="apple"', false);
    $view->assertSee('Apple');
    $view->assertSee('data-value="banana"', false);
    $view->assertSee('Banana');
});

it('marks the selected option with aria-selected', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\', \'banana\' => \'Banana\']" value="banana" />');

    $view->assertSee('aria-selected="true"', false);
});

it('shows the selected label in the trigger', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\', \'banana\' => \'Banana\']" value="banana" />');

    $view->assertSee('Banana');
});

// --- Search ---

it('renders the search input by default', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" />');

    $view->assertSee('data-combobox-target="filter"', false);
    $view->assertSee('role="combobox"', false);
    $view->assertSee('Search entries...');
});

it('hides the search when searchable is false', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" :searchable="false" />');

    $view->assertDontSee('data-combobox-target="filter"', false);
});

it('shows label for empty string value option', function () {
    $view = $this->blade('<x-hwc::combobox name="status" :options="[\'\' => \'None\', \'active\' => \'Active\']" value="" />');

    $view->assertSee('None');
    $view->assertSee('aria-selected="true"', false);
});

it('uses custom search placeholder', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" search-placeholder="Find..." />');

    $view->assertSee('Find...');
    $view->assertDontSee('Search entries...');
});

// --- Placeholder ---

it('renders placeholder text when no value selected', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" placeholder="Choose a fruit" />');

    $view->assertSee('Choose a fruit');
});

// --- Grouped options ---

it('renders grouped options with headings', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'Fruits\' => [\'apple\' => \'Apple\', \'banana\' => \'Banana\']]" />');

    $view->assertSee('role="group"', false);
    $view->assertSee('role="heading"', false);
    $view->assertSee('Fruits');
});

// --- Id derivation ---

it('auto-generates an id when not provided', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" />');

    $view->assertSee('id="combobox-', false);
});

it('uses explicit id and derives sub-ids', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" id="my-combo" :options="[\'apple\' => \'Apple\']" />');

    $view->assertSee('id="my-combo"', false);
    $view->assertSee('id="my-combo-trigger"', false);
    $view->assertSee('id="my-combo-popover"', false);
    $view->assertSee('id="my-combo-listbox"', false);
});

it('uses aria-controls and aria-labelledby pointing to derived ids', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" id="c" :options="[\'apple\' => \'Apple\']" />');

    $view->assertSee('aria-controls="c-listbox"', false);
    $view->assertSee('aria-labelledby="c-trigger"', false);
});

// --- Targets ---

it('renders all combobox targets', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" />');

    $view->assertSee('data-combobox-target="trigger"', false);
    $view->assertSee('data-combobox-target="selectedLabel"', false);
    $view->assertSee('data-combobox-target="popover"', false);
    $view->assertSee('data-combobox-target="filter"', false);
    $view->assertSee('data-combobox-target="listbox"', false);
    $view->assertSee('data-combobox-target="input"', false);
});

// --- Class merge ---

it('merges class on wrapper', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" class="w-64" />');

    $view->assertSee('class="w-64"', false);
});

it('merges trigger-class on the trigger button', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" trigger-class="btn-outline" />');

    $view->assertSee('btn-outline', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes on wrapper', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" data-test="x" />');

    $view->assertSee('data-test="x"', false);
});
