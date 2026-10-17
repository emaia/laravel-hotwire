<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Emaia\LaravelHotwireTurbo\TurboStreamBuilder;

beforeEach(function () {
    TurboStreamBuilder::flushMacros();
    (new LaravelHotwireServiceProvider($this->app))->bootBladeIntegration();
});

afterEach(function () {
    TurboStreamBuilder::flushMacros();
    request()->headers->remove('Turbo-Frame');
    request()->headers->remove('X-Hotwire-Overlay-Host');
    request()->headers->remove('X-Hotwire-Overlay-Instance');
    request()->headers->remove('X-Hotwire-Overlay-Origin');
});

it('resolves an explicit origin from a primary or nested overlay request', function () {
    $token = str_repeat('a', 32);
    $id = 'modal--'.$token;
    request()->headers->set('Turbo-Frame', $id);
    request()->headers->set('X-Hotwire-Overlay-Host', 'modal');
    request()->headers->set('X-Hotwire-Overlay-Instance', $token);

    expect(request()->overlayOrigin())->toBe($id);

    request()->headers->remove('X-Hotwire-Overlay-Host');
    request()->headers->remove('X-Hotwire-Overlay-Instance');
    request()->headers->set('Turbo-Frame', 'customer-details');
    request()->headers->set('X-Hotwire-Overlay-Origin', $id);

    expect(request()->overlayOrigin())->toBe($id);
});

it('returns null for a page or normal Frame request instead of guessing an origin', function () {
    expect(request()->overlayOrigin())->toBeNull();

    request()->headers->set('Turbo-Frame', 'modal');
    expect(request()->overlayOrigin())->toBeNull();

    request()->headers->set('Turbo-Frame', 'customer-tabs');
    expect(request()->overlayOrigin())->toBeNull();
});

it('rejects inconsistent overlay headers instead of treating them as absent', function () {
    $token = str_repeat('a', 32);
    request()->headers->set('Turbo-Frame', 'modal--'.$token);
    request()->headers->set('X-Hotwire-Overlay-Host', 'modal');

    expect(fn () => request()->overlayOrigin())->toThrow(InvalidArgumentException::class, 'Invalid overlay instance context');

    request()->headers->remove('X-Hotwire-Overlay-Host');
    request()->headers->set('X-Hotwire-Overlay-Origin', 'not an origin');
    expect(fn () => request()->overlayOrigin())->toThrow(InvalidArgumentException::class, 'Invalid overlay origin context');
});

// --- Return to the opener ---

it('returns a fragment to the captured parent region of an explicit origin', function () {
    $origin = 'modal--'.str_repeat('a', 32);
    $html = turbo_stream()
        ->returnFromOverlay($origin, '<option value="7" selected>Acme</option>')
        ->closeOverlay($origin)
        ->toHtml();

    expect($html)
        ->toContain('action="hotwire-overlay-return" target="'.$origin.'"')
        ->toContain('<option value="7" selected>Acme</option>')
        ->not->toContain('data-overlay-result');
});

it('includes the saved record identity for a parent region with its own renderer', function (int|string $result, string $expected) {
    $origin = 'modal--'.str_repeat('a', 32);
    $view = view()->file(__DIR__.'/../Fixtures/views/overlay-content.blade.php', ['title' => 'Acme <Ltd>']);

    expect(turbo_stream()->returnFromOverlay($origin, $view, result: $result)->toHtml())
        ->toContain('data-overlay-result="'.$expected.'"')
        ->toContain('Acme &lt;Ltd&gt;');
})->with([
    'integer key' => [42, '42'],
    'uuid key' => ['0b6f4c1e-7d2a-4a7b-9b1e-1f2d3c4b5a69', '0b6f4c1e-7d2a-4a7b-9b1e-1f2d3c4b5a69'],
]);

it('requires an explicit origin and a non-empty fallback fragment to return', function () {
    expect(fn () => turbo_stream()->returnFromOverlay('  ', '<option>Acme</option>'))
        ->toThrow(InvalidArgumentException::class, 'Overlay origin');
    expect(fn () => turbo_stream()->returnFromOverlay('modal--'.str_repeat('a', 32), ' '))
        ->toThrow(InvalidArgumentException::class, 'fragment');
    expect(fn () => turbo_stream()->returnFromOverlay('modal--'.str_repeat('a', 32), '<option>Acme</option>', result: ''))
        ->toThrow(InvalidArgumentException::class, 'result');
});

it('does not replace an application returnFromOverlay macro', function () {
    TurboStreamBuilder::flushMacros();
    TurboStreamBuilder::macro('returnFromOverlay', fn () => 'application macro');

    (new LaravelHotwireServiceProvider($this->app))->bootBladeIntegration();

    expect(turbo_stream()->returnFromOverlay())->toBe('application macro');
});

it('closes only the explicitly named overlay while retaining other stream operations', function () {
    $origin = 'modal--'.str_repeat('a', 32);
    $html = turbo_stream()
        ->update('sale_42', '<span>Saved</span>')
        ->closeOverlay($origin)
        ->toast('success', 'Saved')
        ->toHtml();

    expect(substr_count($html, '<turbo-stream'))->toBe(3)
        ->and($html)->toContain('action="update" target="sale_42"')
        ->toContain('action="hotwire-overlay-close" target="'.$origin.'"')
        ->toContain('action="append" target="toaster"')
        ->not->toContain('action="update" target="'.$origin.'"');
});

it('requires an explicit, non-blank origin instead of falling back to the top overlay', function () {
    expect(fn () => turbo_stream()->closeOverlay())->toThrow(ArgumentCountError::class);
    expect(fn () => turbo_stream()->closeOverlay('  '))->toThrow(InvalidArgumentException::class, 'Overlay origin');
});

it('can name a static overlay root without clearing its markup', function () {
    expect(turbo_stream()->closeOverlay('settings-dialog')->toHtml())
        ->toContain('action="hotwire-overlay-close" target="settings-dialog"')
        ->not->toContain('action="update"');
});

it('does not replace an application closeOverlay macro', function () {
    TurboStreamBuilder::flushMacros();
    TurboStreamBuilder::macro('closeOverlay', fn () => 'application macro');

    (new LaravelHotwireServiceProvider($this->app))->bootBladeIntegration();

    expect(turbo_stream()->closeOverlay())->toBe('application macro');
});
