<?php

use Emaia\LaravelHotwire\Http\Middleware\BypassResponseCache;
use Emaia\LaravelHotwire\Http\Middleware\CachePublicResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

// --- Version support ---

it('supports only Spatie ResponseCache 8.7 releases', function (?string $version, bool $supported) {
    expect(BypassResponseCache::supports($version))->toBe($supported);
})->with([
    'normalized 8.7.0' => ['8.7.0.0', true],
    'patch release' => ['8.7.12.0', true],
    'next minor' => ['8.8.0.0', false],
    'previous minor' => ['8.6.3.0', false],
    'next major' => ['9.0.0.0', false],
    'development branch' => ['dev-main', false],
    'not installed' => [null, false],
]);

it('resolves the origin cache alias to the adapter only for a supported version', function () {
    $alias = app('router')->getMiddleware()['hotwire.cache.spatie'] ?? null;

    expect($alias)->toBe(BypassResponseCache::supports(BypassResponseCache::installedVersion())
        ? CachePublicResponse::class
        : BypassResponseCache::class);
});

// --- Fallback ---

it('serves the route without origin caching and warns once per process', function () {
    Log::spy();
    $middleware = new BypassResponseCache('8.8.0.0');
    $next = fn () => new Response('Rendered');

    expect($middleware->handle(Request::create('/'), $next)->getContent())->toBe('Rendered')
        ->and($middleware->handle(Request::create('/'), $next)->getContent())->toBe('Rendered');

    Log::shouldHaveReceived('warning')->once()->withArgs(
        fn (string $message, array $context) => str_contains($message, '8.7.x') && $context === ['installed' => '8.8.0.0'],
    );
});
