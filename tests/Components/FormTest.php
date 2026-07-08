<?php

use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
});

// --- Basic render ---

it('renders a form element with the slot', function () {
    $view = $this->blade('<x-hw::form><span>x</span></x-hw::form>');

    $view->assertSee('<form', false);
    $view->assertSee('</form>', false);
    $view->assertSee('<span>x</span>', false);
});

it('defaults to method="post"', function () {
    $view = $this->blade('<x-hw::form><span>x</span></x-hw::form>');

    $view->assertSee('method="post"', false);
});

it('passes through action and method', function () {
    $view = $this->blade('<x-hw::form action="/items" method="get"><span>x</span></x-hw::form>');

    $view->assertSee('action="/items"', false);
    $view->assertSee('method="get"', false);
});

it('renders method exactly once when user overrides default', function () {
    $view = $this->blade('<x-hw::form action="/items" method="get"><span>x</span></x-hw::form>');

    $html = (string) $view;
    expect(substr_count($html, 'method='))->toBe(1);
    $view->assertDontSee('method="post"', false);
});

it('renders default method=post when user does not override', function () {
    $view = $this->blade('<x-hw::form action="/items"><span>x</span></x-hw::form>');

    $html = (string) $view;
    expect(substr_count($html, 'method='))->toBe(1);
    $view->assertSee('method="post"', false);
});

// --- Controllers ---

it('does not add any controller by default', function () {
    $view = $this->blade('<x-hw::form><span>x</span></x-hw::form>');

    $view->assertDontSee('data-controller', false);
});

it('adds auto-submit controller when auto-submit is true', function () {
    $view = $this->blade('<x-hw::form auto-submit><span>x</span></x-hw::form>');

    $view->assertSee('data-controller="auto-submit"', false);
});

it('adds unsaved-changes controller when unsaved-changes is true', function () {
    $view = $this->blade('<x-hw::form unsaved-changes><span>x</span></x-hw::form>');

    $view->assertSee('data-controller="unsaved-changes"', false);
});

it('adds clean-query-params controller when clean-query-params is true', function () {
    $view = $this->blade('<x-hw::form method="get" clean-query-params><span>x</span></x-hw::form>');

    $view->assertSee('data-controller="clean-query-params"', false);
});

it('adds error-scroll controller when error-scroll is true', function () {
    $view = $this->blade('<x-hw::form error-scroll><span>x</span></x-hw::form>');

    $view->assertSee('data-controller="error-scroll"', false);
});

it('combines multiple controllers separated by space', function () {
    $view = $this->blade('<x-hw::form auto-submit unsaved-changes><span>x</span></x-hw::form>');

    $view->assertSee('data-controller="auto-submit unsaved-changes"', false);
});

it('combines all controllers', function () {
    $view = $this->blade('<x-hw::form auto-submit unsaved-changes error-scroll clean-query-params><span>x</span></x-hw::form>');

    $view->assertSee('data-controller="auto-submit unsaved-changes error-scroll clean-query-params"', false);
});

it('merges user data-controller with internal controllers', function () {
    $view = $this->blade('<x-hw::form data-controller="foo" auto-submit unsaved-changes error-scroll><span>x</span></x-hw::form>');

    $view->assertSee('data-controller="auto-submit unsaved-changes error-scroll foo"', false);
});

it('merges inline stimulus attributes with internal controllers', function () {
    $view = $this->blade('<x-hw::form auto-submit :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'submit\')"><span>x</span></x-hw::form>');

    $view->assertSee('data-controller="auto-submit analytics"', false);
    $view->assertSee('data-action="submit->analytics#track"', false);
});

// --- Class merge ---

it('merges custom class on the form element', function () {
    $view = $this->blade('<x-hw::form class="space-y-4"><span>x</span></x-hw::form>');

    $view->assertSee('class="space-y-4"', false);
});

// --- CSRF ---

it('includes csrf by default with method=post', function () {
    $view = $this->blade('<x-hw::form><span>x</span></x-hw::form>');

    $view->assertSee('_token', false);
});

it('does not include csrf on GET forms', function () {
    $view = $this->blade('<x-hw::form method="get"><span>x</span></x-hw::form>');

    $view->assertDontSee('_token', false);
});

it('includes csrf on non-GET methods like put and delete', function () {
    $view = $this->blade('<x-hw::form method="put"><span>x</span></x-hw::form>');

    $view->assertSee('_token', false);

    $view = $this->blade('<x-hw::form method="delete"><span>x</span></x-hw::form>');

    $view->assertSee('_token', false);
});

// --- Method spoofing ---

it('includes _method hidden input for PUT forms', function () {
    $view = $this->blade('<x-hw::form method="put"><span>x</span></x-hw::form>');

    $view->assertSee('name="_method"', false);
    $view->assertSee('value="put"', false);
});

it('includes _method hidden input for PATCH forms', function () {
    $view = $this->blade('<x-hw::form method="patch"><span>x</span></x-hw::form>');

    $view->assertSee('name="_method"', false);
    $view->assertSee('value="patch"', false);
});

it('includes _method hidden input for DELETE forms', function () {
    $view = $this->blade('<x-hw::form method="delete"><span>x</span></x-hw::form>');

    $view->assertSee('name="_method"', false);
    $view->assertSee('value="delete"', false);
});

it('does not include _method for POST forms', function () {
    $view = $this->blade('<x-hw::form method="post"><span>x</span></x-hw::form>');

    $view->assertDontSee('_method', false);
});

it('does not include _method for default POST forms', function () {
    $view = $this->blade('<x-hw::form><span>x</span></x-hw::form>');

    $view->assertDontSee('_method', false);
});

it('does not include _method for GET forms', function () {
    $view = $this->blade('<x-hw::form method="get"><span>x</span></x-hw::form>');

    $view->assertDontSee('_method', false);
});

it('renders method="post" on form tag for PUT forms', function () {
    $view = $this->blade('<x-hw::form method="put"><span>x</span></x-hw::form>');

    $view->assertSee('method="post"', false);
    $view->assertDontSee('method="put"', false);
});

it('renders method="post" on form tag for DELETE forms', function () {
    $view = $this->blade('<x-hw::form method="delete"><span>x</span></x-hw::form>');

    $view->assertSee('method="post"', false);
    $view->assertDontSee('method="delete"', false);
});

it('renders method exactly once for spoofed forms', function () {
    $view = $this->blade('<x-hw::form method="put"><span>x</span></x-hw::form>');

    $html = (string) $view;
    expect(substr_count($html, 'method='))->toBe(1);
});

// --- Track Frame Src ---

it('outputs hidden _turbo_frame_src input when track-frame-src is true', function () {
    $view = $this->blade('<x-hw::form track-frame-src><span>x</span></x-hw::form>');

    $view->assertSee('_turbo_frame_src', false);
});

it('does not output _turbo_frame_src when track-frame-src is not set', function () {
    $view = $this->blade('<x-hw::form><span>x</span></x-hw::form>');

    $view->assertDontSee('_turbo_frame_src', false);
});

it('does not render track-frame-src as an html attribute', function () {
    $view = $this->blade('<x-hw::form track-frame-src><span>x</span></x-hw::form>');

    $view->assertDontSee('track-frame-src', false);
});

it('does not render auto-submit as an html attribute', function () {
    $view = $this->blade('<x-hw::form auto-submit><span>x</span></x-hw::form>');

    $view->assertDontSee(' auto-submit', false);
});

it('does not render unsaved-changes as an html attribute', function () {
    $view = $this->blade('<x-hw::form unsaved-changes><span>x</span></x-hw::form>');

    $view->assertDontSee(' unsaved-changes', false);
});

it('does not render clean-query-params as an html attribute', function () {
    $view = $this->blade('<x-hw::form clean-query-params><span>x</span></x-hw::form>');

    $view->assertDontSee(' clean-query-params', false);
});

it('does not render error-scroll as an html attribute', function () {
    $view = $this->blade('<x-hw::form error-scroll><span>x</span></x-hw::form>');

    $view->assertDontSee(' error-scroll', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hw::form data-turbo-frame="modal"><span>x</span></x-hw::form>');

    $view->assertSee('data-turbo-frame="modal"', false);
});

// --- enctype ---

it('does not render enctype by default', function () {
    $view = $this->blade('<x-hw::form><span>x</span></x-hw::form>');

    $view->assertDontSee('enctype=', false);
});

it('renders enctype when provided as prop', function () {
    $view = $this->blade('<x-hw::form enctype="multipart/form-data"><span>x</span></x-hw::form>');

    $view->assertSee('enctype="multipart/form-data"', false);
});

it('renders enctype="text/plain" when set', function () {
    $view = $this->blade('<x-hw::form enctype="text/plain"><span>x</span></x-hw::form>');

    $view->assertSee('enctype="text/plain"', false);
});

it('renders enctype exactly once when set', function () {
    $view = $this->blade('<x-hw::form enctype="multipart/form-data"><span>x</span></x-hw::form>');

    $html = (string) $view;
    expect(substr_count($html, 'enctype='))->toBe(1);
});
