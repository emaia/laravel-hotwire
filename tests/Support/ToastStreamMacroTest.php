<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Emaia\LaravelHotwireTurbo\TurboStreamBuilder;

// Macros live in a static registry and would leak between tests.
beforeEach(function () {
    TurboStreamBuilder::flushMacros();
    (new LaravelHotwireServiceProvider($this->app))->packageBooted();
});

afterEach(function () {
    TurboStreamBuilder::flushMacros();
});

it('registers a toast macro on the stream builder', function () {
    expect(TurboStreamBuilder::hasMacro('toast'))->toBeTrue();
});

it('appends a rendered toast to the viewport', function () {
    $html = turbo_stream()->toast('success', 'Saved')->toHtml();

    expect($html)
        ->toContain('action="append"')
        ->toContain('target="toaster"')
        ->toContain('data-controller="toast"')
        ->toContain('data-toast-message-value="Saved"')
        ->toContain('data-toast-type-value="success"');
});

it('forwards the optional description and position', function () {
    $html = turbo_stream()
        ->toast('error', 'Failed', 'Check the required fields', 'top-end')
        ->toHtml();

    expect($html)
        ->toContain('data-toast-description-value="Check the required fields"')
        ->toContain('data-toast-position-value="top-end"');
});

it('omits the optional values when they are not given', function () {
    $html = turbo_stream()->toast('info', 'Heads up')->toHtml();

    expect($html)
        ->not->toContain('description-value')
        ->not->toContain('position-value');
});

it('targets a custom viewport id', function () {
    // A stream appending into an unknown target fails silently, and the id is a prop.
    $html = turbo_stream()->toast('success', 'Saved', target: 'my-toaster')->toHtml();

    expect($html)->toContain('target="my-toaster"');
});

it('renders through the hw alias even when the prefix is customized', function () {
    config()->set('hotwire.prefix', 'custom');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->packageBooted();

    expect(turbo_stream()->toast('success', 'Saved')->toHtml())
        ->toContain('data-toast-message-value="Saved"');
});

it('chains with other streams', function () {
    $html = turbo_stream()
        ->refresh(method: 'morph')
        ->toast('error', 'Could not favorite this post.')
        ->toHtml();

    expect($html)
        ->toContain('action="refresh"')
        ->toContain('data-toast-type-value="error"');
});

it('leaves an application macro of the same name alone', function () {
    TurboStreamBuilder::flushMacros();
    TurboStreamBuilder::macro('toast', fn (): string => 'application macro');

    (new LaravelHotwireServiceProvider($this->app))->packageBooted();

    expect(turbo_stream()->toast())->toBe('application macro');
});

it('leaves the flashed message for the layout', function () {
    session()->flash('success', 'Item created');

    turbo_stream()->toast('error', 'Failed')->toHtml();

    // The macro renders <x-hw::toast> with an explicit message, so it must not claim the session's
    // — the toaster in the layout still has to render that one.
    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-toast-message-value="Item created"', false);
});
