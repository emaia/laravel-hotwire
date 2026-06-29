<?php

use Emaia\LaravelHotwire\Components\Drawer;

it('renders the controller, container and panel targets', function () {
    $view = $this->blade('
        <x-hwc::drawer>
            <p>Drawer body</p>
        </x-hwc::drawer>
    ');

    $view->assertSee('data-controller="drawer"', false);
    $view->assertSee('data-drawer-target="container"', false);
    $view->assertSee('data-drawer-target="panel"', false);
    $view->assertSee('role="dialog"', false);
    $view->assertSee('aria-modal="true"', false);
    $view->assertSee('Drawer body');
});

it('renders the trigger slot before the dialog', function () {
    $view = $this->blade('
        <x-hwc::drawer>
            <x-slot:trigger>
                <button data-action="drawer#open">Open drawer</button>
            </x-slot:trigger>
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertSee('Open drawer');
    $view->assertSee('data-action="drawer#open"', false);
});

it('auto-generates an id when none is given', function () {
    $view = $this->blade('
        <x-hwc::drawer>
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertSee('id="drawer-', false);
});

it('respects an explicit id', function () {
    $view = $this->blade('
        <x-hwc::drawer id="nav">
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertSee('id="nav"', false);
});

it('starts hidden and pre-positioned off-canvas', function () {
    $view = $this->blade('
        <x-hwc::drawer>
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertSee(' hidden', false);
    $view->assertSee('opacity-0', false);
});

// --- direction support: left (default), right, top, bottom ---

it('defaults to the left position', function () {
    $view = $this->blade('
        <x-hwc::drawer>
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertSee('data-drawer-panel-hidden-class="-translate-x-full"', false);
    $view->assertSee('data-drawer-panel-visible-class="translate-x-0"', false);
    $view->assertSee('inset-y-0 left-0', false);
    $view->assertSee('style="width: 320px"', false);
});

it('positions on the right with matching axis classes', function () {
    $view = $this->blade('
        <x-hwc::drawer position="right">
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertSee('data-drawer-panel-hidden-class="translate-x-full"', false);
    $view->assertSee('data-drawer-panel-visible-class="translate-x-0"', false);
    $view->assertSee('inset-y-0 right-0', false);
    $view->assertSee('style="width: 320px"', false);
});

it('positions on the top with vertical transforms and height styling', function () {
    $view = $this->blade('
        <x-hwc::drawer position="top" size="40vh">
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertSee('data-drawer-panel-hidden-class="-translate-y-full"', false);
    $view->assertSee('data-drawer-panel-visible-class="translate-y-0"', false);
    $view->assertSee('inset-x-0 top-0', false);
    $view->assertSee('style="height: 40vh"', false);
});

it('positions on the bottom with vertical transforms and height styling', function () {
    $view = $this->blade('
        <x-hwc::drawer position="bottom" size="50vh">
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertSee('data-drawer-panel-hidden-class="translate-y-full"', false);
    $view->assertSee('data-drawer-panel-visible-class="translate-y-0"', false);
    $view->assertSee('inset-x-0 bottom-0', false);
    $view->assertSee('style="height: 50vh"', false);
});

it('throws on an invalid position', function () {
    expect(fn () => new Drawer(position: 'diagonal'))->toThrow(InvalidArgumentException::class);
});

it('size maps to width for horizontal drawers and height for vertical ones', function () {
    $left = $this->blade('<x-hwc::drawer position="left" size="400px"><p>x</p></x-hwc::drawer>');
    $left->assertSee('style="width: 400px"', false);

    $right = $this->blade('<x-hwc::drawer position="right" size="400px"><p>x</p></x-hwc::drawer>');
    $right->assertSee('style="width: 400px"', false);

    $top = $this->blade('<x-hwc::drawer position="top" size="400px"><p>x</p></x-hwc::drawer>');
    $top->assertSee('style="height: 400px"', false);

    $bottom = $this->blade('<x-hwc::drawer position="bottom" size="400px"><p>x</p></x-hwc::drawer>');
    $bottom->assertSee('style="height: 400px"', false);
});

it('exposes the hwc-* class hooks on root, container, backdrop, panel and close button', function () {
    $view = $this->blade('
        <x-hwc::drawer>
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertSee('hwc-drawer', false);
    $view->assertSee('hwc-drawer-container', false);
    $view->assertSee('hwc-drawer-backdrop', false);
    $view->assertSee('hwc-drawer-panel', false);
    $view->assertSee('hwc-drawer-close', false);
});

it('omits the backdrop when disabled', function () {
    $view = $this->blade('
        <x-hwc::drawer :backdrop="false">
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertDontSee('data-drawer-target="backdrop"', false);
    $view->assertDontSee('hwc-drawer-backdrop', false);
});

it('omits the close button when disabled', function () {
    $view = $this->blade('
        <x-hwc::drawer :close-button="false">
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertDontSee('hwc-drawer-close', false);
});

it('appends extra class on the panel', function () {
    $view = $this->blade('
        <x-hwc::drawer class="bg-sidebar text-sidebar-foreground">
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertSee('bg-sidebar text-sidebar-foreground', false);
});

it('unions a user-supplied data-controller', function () {
    $view = $this->blade('
        <x-hwc::drawer data-controller="analytics">
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertSee('data-controller="drawer analytics"', false);
});

it('filters user-supplied data-drawer-* attributes to prevent conflicts', function () {
    $view = $this->blade('
        <x-hwc::drawer data-drawer-hidden-class="foo">
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertSee('data-drawer-hidden-class="opacity-0 pointer-events-none"', false);
    $view->assertDontSee('data-drawer-hidden-class="foo"', false);
});

it('wires turbo:before-cache to closeForCache for a clean snapshot', function () {
    $view = $this->blade('
        <x-hwc::drawer>
            <p>Body</p>
        </x-hwc::drawer>
    ');

    $view->assertSee('turbo:before-cache@window->drawer#closeForCache', false);
});
