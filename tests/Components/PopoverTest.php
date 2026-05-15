<?php

use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
});

// --- Plain render ---

it('renders the popover wrapper with data-controller', function () {
    $view = $this->blade('<x-hwc::popover>body</x-hwc::popover>');

    $view->assertSee('data-controller="popover"', false);
});

it('renders the trigger button with ARIA wiring', function () {
    $view = $this->blade('<x-hwc::popover>body</x-hwc::popover>');

    $view->assertSee('<button', false);
    $view->assertSee('type="button"', false);
    $view->assertSee('data-popover-target="trigger"', false);
    $view->assertSee('aria-haspopup="dialog"', false);
    $view->assertSee('aria-expanded="false"', false);
});

it('renders the content with role=dialog and aria-hidden', function () {
    $view = $this->blade('<x-hwc::popover>body</x-hwc::popover>');

    $view->assertSee('data-popover-target="content"', false);
    $view->assertSee('data-popover', false);
    $view->assertSee('role="dialog"', false);
    $view->assertSee('aria-hidden="true"', false);
});

it('renders the default slot inside the content', function () {
    $view = $this->blade('<x-hwc::popover>my body content</x-hwc::popover>');

    $view->assertSee('my body content');
});

it('renders the trigger slot content', function () {
    $view = $this->blade('
        <x-hwc::popover>
            <x-slot:trigger>
                Click me
            </x-slot:trigger>
            body
        </x-hwc::popover>
    ');

    $view->assertSee('Click me');
});

it('falls back to "Open" when no trigger slot is provided', function () {
    $view = $this->blade('<x-hwc::popover>body</x-hwc::popover>');

    $view->assertSee('Open');
});

it('keeps the wrapper position: relative for absolute content anchoring', function () {
    $view = $this->blade('<x-hwc::popover>body</x-hwc::popover>');

    $view->assertSee('style="position: relative"', false);
});

// --- Id derivation ---

it('auto-generates an id when not provided', function () {
    $view = $this->blade('<x-hwc::popover>body</x-hwc::popover>');

    $view->assertSee('id="popover-', false);
});

it('uses explicit id and derives sub-ids', function () {
    $view = $this->blade('<x-hwc::popover id="my-pop">body</x-hwc::popover>');

    $view->assertSee('id="my-pop"', false);
    $view->assertSee('id="my-pop-trigger"', false);
    $view->assertSee('id="my-pop-content"', false);
});

it('wires aria-controls and aria-labelledby to derived ids', function () {
    $view = $this->blade('<x-hwc::popover id="p">body</x-hwc::popover>');

    $view->assertSee('aria-controls="p-content"', false);
    $view->assertSee('aria-labelledby="p-trigger"', false);
});

// --- Class merge ---

it('merges class on the wrapper', function () {
    $view = $this->blade('<x-hwc::popover class="popover">body</x-hwc::popover>');

    $view->assertSee('class="popover"', false);
});

it('applies trigger-class to the trigger button', function () {
    $view = $this->blade('<x-hwc::popover trigger-class="btn-outline">body</x-hwc::popover>');

    $view->assertSee('class="btn-outline"', false);
});

it('applies content-class to the content div', function () {
    $view = $this->blade('<x-hwc::popover content-class="w-80">body</x-hwc::popover>');

    $view->assertSee('class="w-80"', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes on the wrapper', function () {
    $view = $this->blade('<x-hwc::popover data-test="x">body</x-hwc::popover>');

    $view->assertSee('data-test="x"', false);
});

// --- Placement ---

it('defaults placement to left and emits the data-placement attribute', function () {
    $view = $this->blade('<x-hwc::popover>body</x-hwc::popover>');

    $view->assertSee('data-placement="left"', false);
});

it('does not emit positioning inline styles for left placement', function () {
    $view = $this->blade('<x-hwc::popover>body</x-hwc::popover>');

    $view->assertDontSee('right: 0', false);
    $view->assertDontSee('left: auto', false);
});

it('emits inline style and data-placement when placement is right', function () {
    $view = $this->blade('<x-hwc::popover placement="right">body</x-hwc::popover>');

    $view->assertSee('data-placement="right"', false);
    $view->assertSee('style="right: 0; left: auto;"', false);
});

it('falls back to left for an invalid placement value', function () {
    $view = $this->blade('<x-hwc::popover placement="bogus">body</x-hwc::popover>');

    $view->assertSee('data-placement="left"', false);
    $view->assertDontSee('right: 0', false);
});
