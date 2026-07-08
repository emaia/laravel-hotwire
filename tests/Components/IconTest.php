<?php

// --- Rendering ---

it('renders an SVG element for a known icon', function () {
    $view = $this->blade('<x-hw::icon name="x" />');

    $view->assertSee('svg', false)
        ->assertSee('<svg', false)
        ->assertSee('</svg>', false);
});

it('merges user-provided class with the SVG element', function () {
    $view = $this->blade('<x-hw::icon name="x" class="w-6 h-6 text-red-500" />');

    $view->assertSee('class="w-6 h-6 text-red-500"', false);
});

it('passes through extra attributes to the SVG', function () {
    $view = $this->blade('<x-hw::icon name="x" aria-label="Close" data-test="foo" />');

    $view->assertSee('aria-label="Close"', false)
        ->assertSee('data-test="foo"', false);
});

it('includes required SVG attributes', function () {
    $view = $this->blade('<x-hw::icon name="x" />');

    $view->assertSee('xmlns="http://www.w3.org/2000/svg"', false)
        ->assertSee('viewBox="0 0 24 24"', false)
        ->assertSee('fill="none"', false)
        ->assertSee('stroke="currentColor"', false)
        ->assertSee('stroke-width="2"', false)
        ->assertSee('stroke-linecap="round"', false)
        ->assertSee('stroke-linejoin="round"', false);
});

// --- All defined icons ---

it('renders every defined icon without error', function (string $name) {
    $view = $this->blade('<x-hw::icon name="'.$name.'" />');

    $view->assertSee('<svg', false)
        ->assertSee('</svg>', false);
})->with([
    'x',
    'check',
    'chevron-down',
    'chevron-up',
    'chevron-left',
    'chevron-right',
    'search',
    'circle-x',
    'info',
    'alert-triangle',
    'alert-circle',
    'check-circle',
    'arrow-up',
    'arrow-down',
    'arrow-left',
    'arrow-right',
    'ellipsis',
    'copy',
    'eye',
    'eye-off',
    'loader-circle',
    'app-window',
    'code',
    'bold',
    'italic',
    'underline',
    'strikethrough',
    'heading-1',
    'heading-2',
    'heading-3',
    'link',
    'list',
    'list-ordered',
    'quote',
    'code-xml',
    'minus',
    'undo-2',
    'redo-2',
]);

// --- Unknown icons ---

it('renders a fallback when icon name is unknown', function () {
    $view = $this->blade('<x-hw::icon name="nonexistent" />');

    $view->assertSee('svg', false);
});

it('renders at 24x24 by default', function () {
    $view = $this->blade('<x-hw::icon name="x" />');

    $view->assertSee('width="24"', false)
        ->assertSee('height="24"', false);
});

// --- Component registration ---

it('registers with the configured prefix', function () {
    $view = $this->blade('<x-hw::icon name="x" />');

    $view->assertSee('<svg', false);
});
