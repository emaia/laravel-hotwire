<?php

use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
});

// --- Basic render ---

it('renders a form element with the slot', function () {
    $view = $this->blade('<x-hwc::form><span>x</span></x-hwc::form>');

    $view->assertSee('<form', false);
    $view->assertSee('</form>', false);
    $view->assertSee('<span>x</span>', false);
});

it('defaults to method="post"', function () {
    $view = $this->blade('<x-hwc::form><span>x</span></x-hwc::form>');

    $view->assertSee('method="post"', false);
});

it('passes through action and method', function () {
    $view = $this->blade('<x-hwc::form action="/items" method="get"><span>x</span></x-hwc::form>');

    $view->assertSee('action="/items"', false);
    $view->assertSee('method="get"', false);
});

// --- Controllers ---

it('does not add any controller by default', function () {
    $view = $this->blade('<x-hwc::form><span>x</span></x-hwc::form>');

    $view->assertDontSee('data-controller', false);
});

it('adds auto-submit controller when auto-submit is true', function () {
    $view = $this->blade('<x-hwc::form auto-submit><span>x</span></x-hwc::form>');

    $view->assertSee('data-controller="auto-submit"', false);
});

it('adds unsaved-changes controller when unsaved-changes is true', function () {
    $view = $this->blade('<x-hwc::form unsaved-changes><span>x</span></x-hwc::form>');

    $view->assertSee('data-controller="unsaved-changes"', false);
});

it('adds clean-query-params controller when clean-query-params is true', function () {
    $view = $this->blade('<x-hwc::form method="get" clean-query-params><span>x</span></x-hwc::form>');

    $view->assertSee('data-controller="clean-query-params"', false);
});

it('adds remote-form controller when remote is true', function () {
    $view = $this->blade('<x-hwc::form remote><span>x</span></x-hwc::form>');

    $view->assertSee('data-controller="remote-form"', false);
});

it('combines multiple controllers separated by space', function () {
    $view = $this->blade('<x-hwc::form auto-submit unsaved-changes><span>x</span></x-hwc::form>');

    $view->assertSee('data-controller="auto-submit unsaved-changes"', false);
});

it('combines all controllers', function () {
    $view = $this->blade('<x-hwc::form auto-submit unsaved-changes clean-query-params remote><span>x</span></x-hwc::form>');

    $view->assertSee('data-controller="auto-submit unsaved-changes clean-query-params remote-form"', false);
});

it('merges user data-controller with internal controllers', function () {
    $view = $this->blade('<x-hwc::form data-controller="foo" auto-submit unsaved-changes><span>x</span></x-hwc::form>');

    $view->assertSee('data-controller="foo auto-submit unsaved-changes"', false);
});

// --- Class merge ---

it('merges custom class on the form element', function () {
    $view = $this->blade('<x-hwc::form class="space-y-4"><span>x</span></x-hwc::form>');

    $view->assertSee('class="space-y-4"', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hwc::form data-turbo-frame="modal" enctype="multipart/form-data"><span>x</span></x-hwc::form>');

    $view->assertSee('data-turbo-frame="modal"', false);
    $view->assertSee('enctype="multipart/form-data"', false);
});
