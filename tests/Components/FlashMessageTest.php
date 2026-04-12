<?php

use Emaia\LaravelHotwire\Components\FlashMessage\FlashMessage;
use Illuminate\Support\MessageBag;

it('renders with explicit message', function () {
    $view = $this->blade('<x-hwc::flash-message message="Done!" type="success" />');

    $view->assertSee('data-controller="notification--toast"', false);
    $view->assertSee('data-notification--toast-message-value="Done!"', false);
    $view->assertSee('data-notification--toast-type-value="success"', false);
});

it('renders with description', function () {
    $view = $this->blade('<x-hwc::flash-message message="Saved" description="Record updated" type="success" />');

    $view->assertSee('data-notification--toast-description-value="Record updated"', false);
});

it('does not render description attribute when not provided', function () {
    $view = $this->blade('<x-hwc::flash-message message="Saved" type="success" />');

    $view->assertDontSee('description-value', false);
});

it('does not render when no message or session', function () {
    $component = new FlashMessage;

    expect($component->shouldRender())->toBeFalse();
});

it('reads success from session', function () {
    session()->flash('success', 'Item created');

    $view = $this->blade('<x-hwc::flash-message />');

    $view->assertSee('data-notification--toast-message-value="Item created"', false);
    $view->assertSee('data-notification--toast-type-value="success"', false);
});

it('reads error from session', function () {
    session()->flash('error', 'Something failed');

    $view = $this->blade('<x-hwc::flash-message />');

    $view->assertSee('data-notification--toast-message-value="Something failed"', false);
    $view->assertSee('data-notification--toast-type-value="error"', false);
});

it('reads first validation error from session', function () {
    $errors = new MessageBag(['field' => ['Field is required']]);
    session()->flash('errors', $errors);

    $view = $this->blade('<x-hwc::flash-message />');

    $view->assertSee('data-notification--toast-message-value="Field is required"', false);
    $view->assertSee('data-notification--toast-type-value="error"', false);
});

it('reads warning from session', function () {
    session()->flash('warning', 'Watch out');

    $view = $this->blade('<x-hwc::flash-message />');

    $view->assertSee('data-notification--toast-message-value="Watch out"', false);
    $view->assertSee('data-notification--toast-type-value="warning"', false);
});

it('reads info from session', function () {
    session()->flash('info', 'FYI');

    $view = $this->blade('<x-hwc::flash-message />');

    $view->assertSee('data-notification--toast-message-value="FYI"', false);
    $view->assertSee('data-notification--toast-type-value="info"', false);
});

it('explicit message overrides session', function () {
    session()->flash('success', 'From session');

    $view = $this->blade('<x-hwc::flash-message message="From prop" />');

    $view->assertSee('data-notification--toast-message-value="From prop"', false);
});

it('explicit type overrides session type', function () {
    session()->flash('success', 'Done');

    $view = $this->blade('<x-hwc::flash-message type="warning" />');

    $view->assertSee('data-notification--toast-type-value="warning"', false);
});

it('defaults type to default when no session and no prop', function () {
    $component = new FlashMessage(message: 'Test');

    expect($component->finalType)->toBe('default');
});

it('has data-turbo-temporary attribute', function () {
    $view = $this->blade('<x-hwc::flash-message message="Test" />');

    $view->assertSee('data-turbo-temporary', false);
});

it('renders using :: namespace syntax', function () {
    $view = $this->blade('<x-hwc::flash-message message="Done!" type="success" />');

    $view->assertSee('data-notification--toast-message-value="Done!"', false);
    $view->assertSee('data-notification--toast-type-value="success"', false);
});
