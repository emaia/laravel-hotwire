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

it('never guesses an origin from a normal Frame or page request', function () {
    request()->headers->set('Turbo-Frame', 'modal');

    expect(fn () => request()->overlayOrigin())->toThrow(InvalidArgumentException::class, 'Overlay origin');
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
