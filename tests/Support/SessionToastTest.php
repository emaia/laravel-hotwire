<?php

use Emaia\LaravelHotwire\Support\SessionToast;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function sessionToast(): SessionToast
{
    return app(SessionToast::class);
}

// --- Simple flash keys ---

it('resolves the success key', function () {
    session()->flash('success', 'Item created');

    expect(sessionToast()->resolve())->toBe([
        'type' => 'success',
        'message' => 'Item created',
        'description' => null,
        'position' => null,
    ]);
});

it('maps each simple key to its type', function (string $key, string $type) {
    session()->flash($key, 'Message');

    expect(sessionToast()->resolve()['type'])->toBe($type);
})->with([
    ['success', 'success'],
    ['error', 'error'],
    ['warning', 'warning'],
    ['info', 'info'],
]);

it('reads the first validation error as an error toast', function () {
    session()->flash('errors', tap(new ViewErrorBag, fn ($bag) => $bag->put('default', new MessageBag(['email' => ['Email is required']]))));

    expect(sessionToast()->resolve())->toMatchArray([
        'type' => 'error',
        'message' => 'Email is required',
    ]);
});

it('prefers success over the other simple keys', function () {
    session()->flash('info', 'Info');
    session()->flash('warning', 'Warning');
    session()->flash('error', 'Error');
    session()->flash('success', 'Success');

    expect(sessionToast()->resolve()['type'])->toBe('success');
});

it('returns null when the session carries no toast', function () {
    expect(sessionToast()->resolve())->toBeNull();
});

// --- Empty and malformed payloads ---

it('returns null for an empty message', function () {
    session()->flash('success', '');

    expect(sessionToast()->resolve())->toBeNull();
});

it('returns null for an empty error bag', function () {
    session()->flash('errors', new ViewErrorBag);

    expect(sessionToast()->resolve())->toBeNull();
});

it('returns null for a non-scalar message', function () {
    session()->flash('success', ['nested' => 'value']);

    expect(sessionToast()->resolve())->toBeNull();
});

// --- Structured payload ---

it('resolves a structured payload', function () {
    session()->flash('toast', [
        'type' => 'success',
        'message' => 'Task updated',
        'description' => 'Your changes are now live.',
        'position' => 'top-center',
    ]);

    expect(sessionToast()->resolve())->toBe([
        'type' => 'success',
        'message' => 'Task updated',
        'description' => 'Your changes are now live.',
        'position' => 'top-center',
    ]);
});

it('defaults the type of a structured payload', function () {
    session()->flash('toast', ['message' => 'Saved']);

    expect(sessionToast()->resolve())->toMatchArray([
        'type' => 'default',
        'message' => 'Saved',
    ]);
});

it('normalises a bare string payload', function () {
    session()->flash('toast', 'Saved');

    expect(sessionToast()->resolve())->toMatchArray([
        'type' => 'default',
        'message' => 'Saved',
    ]);
});

it('returns null for a structured payload without a message', function () {
    session()->flash('toast', ['type' => 'success']);

    expect(sessionToast()->resolve())->toBeNull();
});

it('prefers the structured payload over the simple keys', function () {
    session()->flash('success', 'From the simple key');
    session()->flash('toast', ['type' => 'info', 'message' => 'From the payload']);

    expect(sessionToast()->resolve())->toMatchArray([
        'type' => 'info',
        'message' => 'From the payload',
    ]);
});

// --- Claiming ---

it('resolves without claiming', function () {
    session()->flash('success', 'Item created');
    $toast = sessionToast();

    expect($toast->resolve())->not->toBeNull()
        ->and($toast->resolve())->not->toBeNull()
        ->and($toast->consume())->not->toBeNull();
});

it('consumes only once', function () {
    session()->flash('success', 'Item created');
    $toast = sessionToast();

    expect($toast->consume())->not->toBeNull()
        ->and($toast->consume())->toBeNull();
});

it('keeps resolving after being consumed', function () {
    session()->flash('success', 'Item created');
    $toast = sessionToast();
    $toast->consume();

    expect($toast->resolve())->toMatchArray(['message' => 'Item created']);
});

it('never claims when the session carries no toast', function () {
    $toast = sessionToast();

    expect($toast->consume())->toBeNull();

    session()->flash('success', 'Arrived later');

    expect($toast->consume())->not->toBeNull();
});

it('leaves the session untouched so the error bag survives', function () {
    session()->flash('errors', tap(new ViewErrorBag, fn ($bag) => $bag->put('default', new MessageBag(['email' => ['Email is required']]))));

    sessionToast()->consume();

    expect(session()->has('errors'))->toBeTrue();
});

// --- Container binding ---

it('is bound once per request', function () {
    expect(app(SessionToast::class))->toBe(app(SessionToast::class));
});

it('is scoped so a new request starts unclaimed', function () {
    session()->flash('success', 'Item created');

    expect(sessionToast()->consume())->not->toBeNull();

    app()->forgetScopedInstances();

    expect(sessionToast()->consume())->not->toBeNull();
});

it('reads the first error of a named bag', function () {
    session()->flash('errors', tap(new ViewErrorBag, fn ($bag) => $bag->put('login', new MessageBag(['email' => ['Email is required']]))));

    expect(sessionToast()->resolve())->toMatchArray([
        'type' => 'error',
        'message' => 'Email is required',
    ]);
});

it('does not let a lower-priority key win over a named bag', function () {
    session()->flash('errors', tap(new ViewErrorBag, fn ($bag) => $bag->put('login', new MessageBag(['email' => ['Email is required']]))));
    session()->flash('info', 'Lower priority');

    expect(sessionToast()->resolve()['message'])->toBe('Email is required');
});

it('skips a bag whose first message is blank', function () {
    session()->flash('errors', tap(new ViewErrorBag, function ($bag) {
        $bag->put('login', new MessageBag(['email' => ['   ']]));
        $bag->put('profile', new MessageBag(['name' => ['Name is required']]));
    }));

    expect(sessionToast()->resolve())->toMatchArray([
        'type' => 'error',
        'message' => 'Name is required',
    ]);
});

it('returns null when every bag is blank', function () {
    session()->flash('errors', tap(new ViewErrorBag, function ($bag) {
        $bag->put('login', new MessageBag(['email' => ['   ']]));
    }));

    expect(sessionToast()->resolve())->toBeNull();
});

it('skips a blank message inside a bag', function () {
    session()->flash('errors', tap(new ViewErrorBag, fn ($bag) => $bag->put('default', new MessageBag([
        'email' => ['   '],
        'name' => ['Name is required'],
    ]))));

    expect(sessionToast()->resolve())->toMatchArray([
        'type' => 'error',
        'message' => 'Name is required',
    ]);
});

it('skips a blank message in a bare message bag', function () {
    session()->flash('errors', new MessageBag([
        'email' => ['   '],
        'name' => ['Name is required'],
    ]));

    expect(sessionToast()->resolve()['message'])->toBe('Name is required');
});

it('reads raw messages past a decorating bag format', function () {
    $messages = new MessageBag(['email' => ['   '], 'name' => ['Name is required']]);
    $messages->setFormat('<li>:message</li>');

    session()->flash('errors', tap(new ViewErrorBag, fn ($bag) => $bag->put('default', $messages)));

    expect(sessionToast()->resolve()['message'])->toBe('Name is required');
});
