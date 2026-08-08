<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;

// --- Defaults ---

it('renders with default props', function () {
    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-controller="toaster"', false);
    $view->assertSee('id="toaster"', false);
    $view->assertSee('data-turbo-permanent', false);
});

it('merges inline stimulus attributes with the toaster controller', function () {
    $view = $this->blade('<x-hw::toaster :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'hotwire:toast\')" />');

    $view->assertSee('data-controller="toaster analytics"', false);
    $view->assertSee('data-action="hotwire:toast->analytics#track"', false);
});

it('emits default stimulus values', function () {
    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-toaster-position-value="bottom-center"', false);
    $view->assertSee('data-toaster-duration-value="4000"', false);
    $view->assertSee('data-toaster-visible-toasts-value="3"', false);
    $view->assertSee('data-toaster-close-button-value="true"', false);
    $view->assertSee('data-toaster-expand-value="false"', false);
    $view->assertSee('data-toaster-auto-disconnect-value="false"', false);
});

// --- Identity and Turbo integration ---

it('uses a custom id when provided', function () {
    $view = $this->blade('<x-hw::toaster id="toaster-root" />');

    $view->assertSee('id="toaster-root"', false);
});

it('omits data-turbo-permanent when disabled', function () {
    $view = $this->blade('<x-hw::toaster :turbo-permanent="false" />');

    $view->assertDontSee('data-turbo-permanent', false);
});

it('applies a custom class on the container div', function () {
    $view = $this->blade('<x-hw::toaster class="z-50 isolate" />');

    $view->assertSee('class="z-50 isolate"', false);
});

// --- Nullable props: omitted when unset ---

it('omits nullable stimulus values when not provided', function () {
    $view = $this->blade('<x-hw::toaster />');

    $view->assertDontSee('class-name-value', false);
    $view->assertDontSee('container-aria-label-value', false);
});

// --- Custom values emission ---

it('emits custom position, duration, and visible toasts', function () {
    $view = $this->blade('
        <x-hw::toaster
            position="top-right"
            :duration="5000"
            :visible-toasts="5"
        />
    ');

    $view->assertSee('data-toaster-position-value="top-right"', false);
    $view->assertSee('data-toaster-duration-value="5000"', false);
    $view->assertSee('data-toaster-visible-toasts-value="5"', false);
});

it('emits boolean props as true/false strings', function () {
    $view = $this->blade('
        <x-hw::toaster
            :close-button="false"
            :expand="true"
            :auto-disconnect="true"
        />
    ');

    $view->assertSee('data-toaster-close-button-value="false"', false);
    $view->assertSee('data-toaster-expand-value="true"', false);
    $view->assertSee('data-toaster-auto-disconnect-value="true"', false);
});

it('emits optional advanced props when provided', function () {
    $view = $this->blade('
        <x-hw::toaster
            class-name="my-toast-list"
            container-aria-label="Notifications"
        />
    ');

    $view->assertSee('data-toaster-class-name-value="my-toast-list"', false);
    $view->assertSee('data-toaster-container-aria-label-value="Notifications"', false);
});

// --- Namespace registration ---

it('renders with hw:: prefix alias', function () {
    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-controller="toaster"', false);
    $view->assertSee('id="toaster"', false);
});

it('registers with custom prefix', function () {
    config()->set('hotwire.prefix', 'custom');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->packageBooted();

    expect(Blade::getClassComponentAliases())->toHaveKey('custom::toaster');
});
