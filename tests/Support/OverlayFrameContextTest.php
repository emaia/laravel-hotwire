<?php

use Emaia\LaravelHotwire\Support\OverlayFrameContext;
use Illuminate\Http\Request;

// --- Request resolution ---

it('resolves the canonical host and origin of a primary overlay request', function (bool $withOrigin) {
    $host = 'customer--editor.v1:main';
    $token = str_repeat('a', 32);
    $origin = $host.'--'.$token;
    $request = Request::create('/');
    $request->headers->add([
        'Turbo-Frame' => $origin,
        'X-Hotwire-Overlay-Host' => $host,
        'X-Hotwire-Overlay-Instance' => $token,
    ]);
    if ($withOrigin) {
        $request->headers->set('X-Hotwire-Overlay-Origin', $origin);
    }

    $context = OverlayFrameContext::fromRequest($request);

    expect($context)->toBeInstanceOf(OverlayFrameContext::class)
        ->and($context->host)->toBe($host)
        ->and($context->frameId)->toBe($origin)
        ->and(OverlayFrameContext::origin($request))->toBe($origin);
})->with([true, false]);

it('extracts only the final instance suffix from an internal Frame origin', function (string $host) {
    $origin = $host.'--'.str_repeat('b', 32);
    $request = Request::create('/');
    $request->headers->add([
        'Turbo-Frame' => 'customer-details',
        'X-Hotwire-Overlay-Origin' => $origin,
    ]);

    $context = OverlayFrameContext::fromRequest($request);

    expect($context)->toBeInstanceOf(OverlayFrameContext::class)
        ->and($context->host)->toBe($host)
        ->and($context->frameId)->toBe($origin)
        ->and(OverlayFrameContext::origin($request))->toBe($origin)
        ->and(OverlayFrameContext::fromHeaders('customer-details', null, null, $origin))->toBeNull();
})->with(['modal', 'customer--editor.v1:main', 'x', str_repeat('a', 100), 'parent--'.str_repeat('c', 32)]);

it('preserves origin-only request resolution without requiring a Turbo Frame header', function () {
    $request = Request::create('/');
    $origin = 'drawer--'.str_repeat('c', 32);
    $request->headers->set('X-Hotwire-Overlay-Origin', $origin);

    $context = OverlayFrameContext::fromRequest($request);

    expect($context)->toBeInstanceOf(OverlayFrameContext::class)
        ->and($context->host)->toBe('drawer')
        ->and($context->frameId)->toBe($origin)
        ->and(OverlayFrameContext::origin($request))->toBe($origin);
});

it('does not infer overlay context from an ordinary Frame name', function (?string $frame) {
    $request = Request::create('/');
    if ($frame !== null) {
        $request->headers->set('Turbo-Frame', $frame);
    }

    expect(OverlayFrameContext::fromRequest($request))->toBeNull()
        ->and(OverlayFrameContext::origin($request))->toBeNull();
})->with([null, 'modal', 'sheet', 'drawer', 'modal--'.str_repeat('a', 32), 'customer-details']);

it('rejects inconsistent request context through the shared header validation', function (array $headers) {
    $request = Request::create('/');
    $request->headers->add($headers);

    expect(fn () => OverlayFrameContext::fromRequest($request))->toThrow(InvalidArgumentException::class)
        ->and(fn () => OverlayFrameContext::origin($request))->toThrow(InvalidArgumentException::class);
})->with([
    'host only' => [['X-Hotwire-Overlay-Host' => 'modal']],
    'instance only' => [['X-Hotwire-Overlay-Instance' => str_repeat('a', 32)]],
    'missing Frame' => [[
        'X-Hotwire-Overlay-Host' => 'modal',
        'X-Hotwire-Overlay-Instance' => str_repeat('a', 32),
    ]],
    'different Frame host' => [[
        'Turbo-Frame' => 'sheet--'.str_repeat('a', 32),
        'X-Hotwire-Overlay-Host' => 'modal',
        'X-Hotwire-Overlay-Instance' => str_repeat('a', 32),
    ]],
    'different instance' => [[
        'Turbo-Frame' => 'modal--'.str_repeat('a', 32),
        'X-Hotwire-Overlay-Host' => 'modal',
        'X-Hotwire-Overlay-Instance' => str_repeat('b', 32),
    ]],
    'ancestor origin contradicts primary' => [[
        'Turbo-Frame' => 'modal--'.str_repeat('a', 32),
        'X-Hotwire-Overlay-Host' => 'modal',
        'X-Hotwire-Overlay-Instance' => str_repeat('a', 32),
        'X-Hotwire-Overlay-Origin' => 'drawer--'.str_repeat('b', 32),
    ]],
    'malformed origin' => [['X-Hotwire-Overlay-Origin' => '../../modal']],
    'empty origin' => [['X-Hotwire-Overlay-Origin' => '']],
    'uppercase token' => [['X-Hotwire-Overlay-Origin' => 'modal--'.str_repeat('A', 32)]],
    'short token' => [['X-Hotwire-Overlay-Origin' => 'modal--'.str_repeat('a', 31)]],
    'long token' => [['X-Hotwire-Overlay-Origin' => 'modal--'.str_repeat('a', 33)]],
    'long host' => [['X-Hotwire-Overlay-Origin' => str_repeat('a', 101).'--'.str_repeat('b', 32)]],
    'invalid host start' => [['X-Hotwire-Overlay-Origin' => '-modal--'.str_repeat('a', 32)]],
    'trailing origin data' => [['X-Hotwire-Overlay-Origin' => 'modal--'.str_repeat('a', 32)."\n"]],
]);
