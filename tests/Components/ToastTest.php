<?php

use Emaia\LaravelHotwire\Components\Toast;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

it('renders with explicit message', function () {
    $view = $this->blade('<x-hw::toast message="Done!" type="success" />');

    $view->assertSee('data-controller="toast"', false);
    $view->assertSee('data-toast-message-value="Done!"', false);
    $view->assertSee('data-toast-type-value="success"', false);
});

it('merges inline stimulus attributes with the toast controller', function () {
    $view = $this->blade('<x-hw::toast message="Done!" type="success" :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'toast:shown\')" />');

    $view->assertSee('data-controller="toast analytics"', false);
    $view->assertSee('data-action="toast:shown->analytics#track"', false);
});

it('renders with description', function () {
    $view = $this->blade('<x-hw::toast message="Saved" description="Record updated" type="success" />');

    $view->assertSee('data-toast-description-value="Record updated"', false);
});

it('does not render description attribute when not provided', function () {
    $view = $this->blade('<x-hw::toast message="Saved" type="success" />');

    $view->assertDontSee('description-value', false);
});

it('renders position when provided', function () {
    $view = $this->blade('<x-hw::toast message="Heads up" type="warning" position="top-center" />');

    $view->assertSee('data-toast-position-value="top-center"', false);
});

it('does not render position attribute when not provided', function () {
    $view = $this->blade('<x-hw::toast message="Saved" type="success" />');

    $view->assertDontSee('position-value', false);
});

it('does not render when no message or session', function () {
    $component = new Toast;

    expect($component->shouldRender())->toBeFalse();
});

it('reads success from session', function () {
    session()->flash('success', 'Item created');

    $view = $this->blade('<x-hw::toast />');

    $view->assertSee('data-toast-message-value="Item created"', false);
    $view->assertSee('data-toast-type-value="success"', false);
});

it('reads error from session', function () {
    session()->flash('error', 'Something failed');

    $view = $this->blade('<x-hw::toast />');

    $view->assertSee('data-toast-message-value="Something failed"', false);
    $view->assertSee('data-toast-type-value="error"', false);
});

it('reads first validation error from session', function () {
    $errors = new MessageBag(['field' => ['Field is required']]);
    session()->flash('errors', $errors);

    $view = $this->blade('<x-hw::toast />');

    $view->assertSee('data-toast-message-value="Field is required"', false);
    $view->assertSee('data-toast-type-value="error"', false);
});

it('reads warning from session', function () {
    session()->flash('warning', 'Watch out');

    $view = $this->blade('<x-hw::toast />');

    $view->assertSee('data-toast-message-value="Watch out"', false);
    $view->assertSee('data-toast-type-value="warning"', false);
});

it('reads info from session', function () {
    session()->flash('info', 'FYI');

    $view = $this->blade('<x-hw::toast />');

    $view->assertSee('data-toast-message-value="FYI"', false);
    $view->assertSee('data-toast-type-value="info"', false);
});

it('explicit message overrides session', function () {
    session()->flash('success', 'From session');

    $view = $this->blade('<x-hw::toast message="From prop" />');

    $view->assertSee('data-toast-message-value="From prop"', false);
});

it('explicit type overrides session type', function () {
    session()->flash('success', 'Done');

    $view = $this->blade('<x-hw::toast type="warning" />');

    $view->assertSee('data-toast-type-value="warning"', false);
});

it('defaults type to default when no session and no prop', function () {
    $component = new Toast(message: 'Test');

    expect($component->finalType)->toBe('default');
});

it('has data-turbo-temporary attribute', function () {
    $view = $this->blade('<x-hw::toast message="Test" />');

    $view->assertSee('data-turbo-temporary', false);
});

it('renders using :: namespace syntax', function () {
    $view = $this->blade('<x-hw::toast message="Done!" type="success" />');

    $view->assertSee('data-toast-message-value="Done!"', false);
    $view->assertSee('data-toast-type-value="success"', false);
});

it('renders with hw:: prefix alias', function () {
    $view = $this->blade('<x-hw::toast message="Done!" type="success" />');

    $view->assertSee('data-controller="toast"', false);
    $view->assertSee('data-toast-message-value="Done!"', false);
    $view->assertSee('data-toast-type-value="success"', false);
});

it('renders class-name when provided', function () {
    $view = $this->blade('<x-hw::toast message="Done!" type="success" class-name="custom-toast" />');

    $view->assertSee('data-toast-class-name-value="custom-toast"', false);
});

it('does not render class-name attribute when not provided', function () {
    $view = $this->blade('<x-hw::toast message="Saved" type="success" />');

    $view->assertDontSee('class-name-value', false);
});

// --- Empty messages ---

it('does not render an empty message', function () {
    $view = $this->blade('<x-hw::toast message="" type="success" />');

    $view->assertDontSee('data-controller="toast"', false);
});

it('does not render a whitespace-only message', function () {
    $view = $this->blade('<x-hw::toast message="   " />');

    $view->assertDontSee('data-controller="toast"', false);
});

it('falls back to the session when the message is empty', function () {
    session()->flash('success', 'Item created');

    $view = $this->blade('<x-hw::toast message="" />');

    $view->assertSee('data-toast-message-value="Item created"', false);
});

// --- Named error bags ---

it('reads the first error of a named bag', function () {
    session()->flash('errors', tap(new ViewErrorBag, fn ($bag) => $bag->put('login', new MessageBag(['email' => ['Email is required']]))));

    $view = $this->blade('<x-hw::toast />');

    $view->assertSee('data-toast-message-value="Email is required"', false);
    $view->assertSee('data-toast-type-value="error"', false);
});

// --- Quoted content ---

it('survives a double quote in the message', function () {
    $view = $this->blade('<x-hw::toast :message="\'He said &quot;hello&quot; to me\'" />');

    // \" would end the attribute at the first quote and drop the rest of the sentence.
    $view->assertSee('data-toast-message-value="He said &quot;hello&quot; to me"', false);
    $view->assertDontSee('\\"', false);
});

it('survives a double quote coming from the session', function () {
    session()->flash('toast', [
        'type' => 'success',
        'message' => 'Renamed to "Q3 report"',
        'description' => 'The old name was "Q2 report"',
    ]);

    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-toast-message-value="Renamed to &quot;Q3 report&quot;"', false);
    $view->assertSee('data-toast-description-value="The old name was &quot;Q2 report&quot;"', false);
});
