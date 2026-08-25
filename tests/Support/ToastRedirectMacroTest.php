<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Http\RedirectResponse;

// Macros live in a static registry and would leak between tests.
beforeEach(function () {
    RedirectResponse::flushMacros();
    (new LaravelHotwireServiceProvider($this->app))->packageBooted();
});

afterEach(function () {
    RedirectResponse::flushMacros();
});

function redirectResponse(): RedirectResponse
{
    return tap(new RedirectResponse('/tasks'), fn (RedirectResponse $response) => $response->setSession(session()->driver()));
}

it('registers a toast macro on the redirect response', function () {
    expect(RedirectResponse::hasMacro('toast'))->toBeTrue();
});

it('flashes the toast payload', function () {
    redirectResponse()->toast('success', 'Task updated');

    expect(session()->get('toast'))->toBe([
        'type' => 'success',
        'message' => 'Task updated',
    ]);
});

it('forwards the optional description and position', function () {
    redirectResponse()->toast('error', 'Failed', 'Check the required fields', 'top-end');

    expect(session()->get('toast'))->toBe([
        'type' => 'error',
        'message' => 'Failed',
        'description' => 'Check the required fields',
        'position' => 'top-end',
    ]);
});

it('returns the redirect response so it stays chainable', function () {
    $response = redirectResponse();

    expect($response->toast('info', 'Heads up'))->toBe($response);
});

it('renders through the toaster on the next request', function () {
    redirectResponse()->toast('success', 'Task updated', 'Your changes are now live.');

    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-toast-message-value="Task updated"', false);
    $view->assertSee('data-toast-description-value="Your changes are now live."', false);
    $view->assertSee('data-toast-type-value="success"', false);
});

it('leaves an application macro of the same name alone', function () {
    RedirectResponse::flushMacros();
    RedirectResponse::macro('toast', fn (): string => 'application macro');

    (new LaravelHotwireServiceProvider($this->app))->packageBooted();

    expect(redirectResponse()->toast())->toBe('application macro');
});

it('survives a double quote through the redirect macro', function () {
    redirectResponse()->toast('success', 'Renamed to "Q3 report"');

    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-toast-message-value="Renamed to &quot;Q3 report&quot;"', false);
});
