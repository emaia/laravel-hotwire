<?php

use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
    app('request')->setLaravelSession(app('session.store'));
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

it('derives id from name when id is not provided', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" />');

    $view->assertSee('id="fruit"', false);
});

it('auto-generates an id when neither name nor id is provided', function () {
    $view = $this->blade('<x-hwc::combobox :options="[\'apple\' => \'Apple\']" />');

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

// --- Stimulus class bindings ---

it('exposes default active and placeholder classes via data attributes', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" />');

    $view->assertSee('data-combobox-active-class="active"', false);
    $view->assertSee('data-combobox-placeholder-class="text-muted-foreground"', false);
});

it('allows overriding active and placeholder classes', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" active-class="bg-accent" placeholder-class="opacity-60" />');

    $view->assertSee('data-combobox-active-class="bg-accent"', false);
    $view->assertSee('data-combobox-placeholder-class="opacity-60"', false);
});

// --- Placement ---

it('defaults placement to left and emits the data-placement attribute', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" />');

    $view->assertSee('data-placement="left"', false);
});

it('does not emit positioning inline styles for left placement', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" />');

    $view->assertDontSee('right: 0', false);
    $view->assertDontSee('left: auto', false);
});

it('emits inline style and data-placement when placement is right', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" placement="right" />');

    $view->assertSee('data-placement="right"', false);
    $view->assertSee('style="right: 0; left: auto;"', false);
});

it('falls back to left for an invalid placement value', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" placement="bogus" />');

    $view->assertSee('data-placement="left"', false);
    $view->assertDontSee('right: 0', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes on wrapper', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" data-test="x" />');

    $view->assertSee('data-test="x"', false);
});

// --- old() / session restore ---

it('restores old value from session after failed submit', function () {
    session()->put('_old_input', ['fruit' => 'banana']);

    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\', \'banana\' => \'Banana\']" value="apple" />');

    $view->assertSee('<span data-combobox-target="selectedLabel">Banana</span>', false);
});

it('prefers old value over the explicit value prop', function () {
    session()->put('_old_input', ['fruit' => 'banana']);

    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\', \'banana\' => \'Banana\']" value="apple" />');

    $view->assertSee('<span data-combobox-target="selectedLabel">Banana</span>', false);
    $view->assertDontSee('<span data-combobox-target="selectedLabel">Apple</span>', false);
});

it('skips old() restore when old is false', function () {
    session()->put('_old_input', ['fruit' => 'banana']);

    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\', \'banana\' => \'Banana\']" value="apple" :old="false" />');

    $view->assertSee('<span data-combobox-target="selectedLabel">Apple</span>', false);
    $view->assertDontSee('<span data-combobox-target="selectedLabel">Banana</span>', false);
});

it('restores old value for array-notation field names', function () {
    session()->put('_old_input', ['items' => [['category' => 'banana']]]);

    $view = $this->blade('<x-hwc::combobox name="items[0][category]" :options="[\'apple\' => \'Apple\', \'banana\' => \'Banana\']" />');

    $view->assertSee('<span data-combobox-target="selectedLabel">Banana</span>', false);
});

it('uses custom error-key for old() lookup', function () {
    session()->put('_old_input', ['cat' => 'banana']);

    $view = $this->blade('<x-hwc::combobox name="fruit" error-key="cat" :options="[\'apple\' => \'Apple\', \'banana\' => \'Banana\']" value="apple" />');

    $view->assertSee('<span data-combobox-target="selectedLabel">Banana</span>', false);
});

it('derives a stable id from name when id is not provided', function () {
    $view = $this->blade('<x-hwc::combobox name="fruit" :options="[\'apple\' => \'Apple\']" />');

    $view->assertSee('id="fruit"', false);
    $view->assertSee('id="fruit-trigger"', false);
});

it('derives id from array-notation name', function () {
    $view = $this->blade('<x-hwc::combobox name="items[0][cat]" :options="[\'apple\' => \'Apple\']" />');

    $view->assertSee('id="items-0-cat"', false);
});
