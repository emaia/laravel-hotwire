<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->requestMacros = (new ReflectionProperty(Request::class, 'macros'))->getValue();
    $this->overlayRequest = Request::create('/');
});

afterEach(function () {
    (new ReflectionProperty(Request::class, 'macros'))->setValue(null, $this->requestMacros);
});

// --- Detection and named host filtering ---

it('detects primary and internal overlay requests through the Request macro', function (bool $internal, string $host) {
    $origin = $host.'--'.str_repeat('a', 32);
    $this->overlayRequest->headers->add([
        'Turbo-Frame' => $internal ? 'customer-details' : $origin,
        'X-Hotwire-Overlay-Origin' => $origin,
    ]);
    if (! $internal) {
        $this->overlayRequest->headers->add([
            'X-Hotwire-Overlay-Host' => $host,
            'X-Hotwire-Overlay-Instance' => str_repeat('a', 32),
        ]);
    }

    expect($this->overlayRequest->wasFromOverlay())->toBeTrue()
        ->and($this->overlayRequest->wasFromOverlay(host: $host))->toBeTrue()
        ->and($this->overlayRequest->wasFromOverlay(host: 'another-host'))->toBeFalse()
        ->and($this->overlayRequest->wasFromOverlay())
        ->toBe($this->overlayRequest->overlayOrigin() !== null);
})->with([false, true])->with(['modal', 'customer--editor.v1:main', 'x', str_repeat('a', 100)]);

it('preserves detection of a primary request without an Origin header', function () {
    $this->overlayRequest->headers->add([
        'Turbo-Frame' => 'x--'.str_repeat('a', 32),
        'X-Hotwire-Overlay-Host' => 'x',
        'X-Hotwire-Overlay-Instance' => str_repeat('a', 32),
    ]);

    expect($this->overlayRequest->wasFromOverlay(host: 'x'))->toBeTrue();
});

it('does not mistake ordinary Frame names for overlays', function (?string $frame) {
    if ($frame !== null) {
        $this->overlayRequest->headers->set('Turbo-Frame', $frame);
    }

    expect($this->overlayRequest->wasFromOverlay())->toBeFalse()
        ->and($this->overlayRequest->wasFromOverlay(host: 'modal'))->toBeFalse()
        ->and($this->overlayRequest->wasFromOverlay())
        ->toBe($this->overlayRequest->overlayOrigin() !== null);
})->with([null, 'modal', 'sheet', 'drawer', 'modal--'.str_repeat('a', 32)]);

it('matches the exact originating host without inferring a family or an ancestor', function () {
    $origin = 'customer-editor--'.str_repeat('b', 32);
    $this->overlayRequest->headers->add([
        'Turbo-Frame' => 'customer-details',
        'X-Hotwire-Overlay-Origin' => $origin,
    ]);

    expect($this->overlayRequest->wasFromOverlay(host: 'customer-editor'))->toBeTrue()
        ->and($this->overlayRequest->wasFromOverlay(host: 'Customer-editor'))->toBeFalse()
        ->and($this->overlayRequest->wasFromOverlay(host: 'modal'))->toBeFalse()
        ->and($this->overlayRequest->wasFromOverlay(host: 'drawer'))->toBeFalse()
        ->and($this->overlayRequest->overlayOrigin())->toBe($origin);
});

// --- Invalid context and filters ---

it('rejects invalid context even when the host filter would not match', function (array $headers) {
    $this->overlayRequest->headers->add($headers);

    expect(fn () => $this->overlayRequest->wasFromOverlay())->toThrow(InvalidArgumentException::class)
        ->and(fn () => $this->overlayRequest->wasFromOverlay(host: 'another-host'))->toThrow(InvalidArgumentException::class);
})->with([
    'partial primary' => [['X-Hotwire-Overlay-Host' => 'modal']],
    'malformed internal origin' => [['X-Hotwire-Overlay-Origin' => 'modal']],
    'contradictory origin' => [[
        'Turbo-Frame' => 'modal--'.str_repeat('a', 32),
        'X-Hotwire-Overlay-Host' => 'modal',
        'X-Hotwire-Overlay-Instance' => str_repeat('a', 32),
        'X-Hotwire-Overlay-Origin' => 'drawer--'.str_repeat('b', 32),
    ]],
]);

it('rejects invalid host filters even without overlay context', function (string $host, bool $inOverlay) {
    if ($inOverlay) {
        $this->overlayRequest->headers->set('X-Hotwire-Overlay-Origin', 'modal--'.str_repeat('a', 32));
    }

    expect(fn () => $this->overlayRequest->wasFromOverlay(host: $host))
        ->toThrow(InvalidArgumentException::class, 'Invalid overlay host');
})->with(['', ' ', '-modal', '/modal', str_repeat('a', 101), "modal\n"])->with([false, true]);

// --- Registration ---

it('preserves an application predicate registered before the package', function () {
    Request::macro('wasFromOverlay', fn (?string $host = null): bool => $host === 'application');

    (new LaravelHotwireServiceProvider($this->app))->bootBladeIntegration();

    expect($this->overlayRequest->wasFromOverlay(host: 'application'))->toBeTrue()
        ->and($this->overlayRequest->wasFromOverlay())->toBeFalse();
});

it('registers the predicate independently of an existing application origin macro', function () {
    $macros = $this->requestMacros;
    unset($macros['wasFromOverlay']);
    (new ReflectionProperty(Request::class, 'macros'))->setValue(null, $macros);
    Request::macro('overlayOrigin', fn (): string => 'application-origin');

    (new LaravelHotwireServiceProvider($this->app))->bootBladeIntegration();

    expect($this->overlayRequest->overlayOrigin())->toBe('application-origin')
        ->and($this->overlayRequest->wasFromOverlay())->toBeFalse();
});

it('keeps the same predicate when package integration is booted again', function () {
    $predicate = $this->requestMacros['wasFromOverlay'] ?? null;
    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->bootBladeIntegration();
    $provider->bootBladeIntegration();
    $macros = (new ReflectionProperty(Request::class, 'macros'))->getValue();

    expect($predicate)->not->toBeNull()
        ->and($macros['wasFromOverlay'])->toBe($predicate);
});
