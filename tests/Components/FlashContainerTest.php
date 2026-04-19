<?php

use Emaia\LaravelHotwire\Components\FlashContainer;
use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;

// --- Defaults ---

it('renders with default props', function () {
    $view = $this->blade('<x-hwc::flash-container />');

    $view->assertSee('data-controller="notification--toaster"', false);
    $view->assertSee('id="flash-container"', false);
    $view->assertSee('data-turbo-permanent', false);
});

it('emits default stimulus values', function () {
    $view = $this->blade('<x-hwc::flash-container />');

    $view->assertSee('data-notification--toaster-position-value="bottom-center"', false);
    $view->assertSee('data-notification--toaster-theme-value="light"', false);
    $view->assertSee('data-notification--toaster-duration-value="4000"', false);
    $view->assertSee('data-notification--toaster-visible-toasts-value="3"', false);
    $view->assertSee('data-notification--toaster-close-button-value="true"', false);
    $view->assertSee('data-notification--toaster-rich-colors-value="true"', false);
    $view->assertSee('data-notification--toaster-expand-value="false"', false);
    $view->assertSee('data-notification--toaster-invert-value="false"', false);
    $view->assertSee('data-notification--toaster-auto-disconnect-value="false"', false);
});

// --- Identity and Turbo integration ---

it('uses a custom id when provided', function () {
    $view = $this->blade('<x-hwc::flash-container id="toaster-root" />');

    $view->assertSee('id="toaster-root"', false);
});

it('omits data-turbo-permanent when disabled', function () {
    $view = $this->blade('<x-hwc::flash-container :turbo-permanent="false" />');

    $view->assertDontSee('data-turbo-permanent', false);
});

it('applies a custom class on the container div', function () {
    $view = $this->blade('<x-hwc::flash-container class="z-50 isolate" />');

    $view->assertSee('class="z-50 isolate"', false);
});

// --- Nullable props: omitted when unset ---

it('omits nullable stimulus values when not provided', function () {
    $view = $this->blade('<x-hwc::flash-container />');

    $view->assertDontSee('gap-value', false);
    $view->assertDontSee('hotkey-value', false);
    $view->assertDontSee('dir-value', false);
    $view->assertDontSee('offset-value', false);
    $view->assertDontSee('mobile-offset-value', false);
    $view->assertDontSee('class-name-value', false);
    $view->assertDontSee('container-aria-label-value', false);
    $view->assertDontSee('custom-aria-label-value', false);
    $view->assertDontSee('swipe-directions-value', false);
});

// --- Custom values emission ---

it('emits custom position, theme, duration, and visible toasts', function () {
    $view = $this->blade('
        <x-hwc::flash-container
            position="top-right"
            theme="dark"
            :duration="5000"
            :visible-toasts="5"
        />
    ');

    $view->assertSee('data-notification--toaster-position-value="top-right"', false);
    $view->assertSee('data-notification--toaster-theme-value="dark"', false);
    $view->assertSee('data-notification--toaster-duration-value="5000"', false);
    $view->assertSee('data-notification--toaster-visible-toasts-value="5"', false);
});

it('emits boolean props as true/false strings', function () {
    $view = $this->blade('
        <x-hwc::flash-container
            :close-button="false"
            :rich-colors="false"
            :expand="true"
            :invert="true"
            :auto-disconnect="true"
        />
    ');

    $view->assertSee('data-notification--toaster-close-button-value="false"', false);
    $view->assertSee('data-notification--toaster-rich-colors-value="false"', false);
    $view->assertSee('data-notification--toaster-expand-value="true"', false);
    $view->assertSee('data-notification--toaster-invert-value="true"', false);
    $view->assertSee('data-notification--toaster-auto-disconnect-value="true"', false);
});

it('emits optional advanced props when provided', function () {
    $view = $this->blade('
        <x-hwc::flash-container
            :gap="10"
            hotkey="alt+T"
            dir="rtl"
            offset="16px"
            mobile-offset="8px"
            class-name="my-toast-list"
            container-aria-label="Notifications"
            custom-aria-label="Alert"
            swipe-directions="left,right"
        />
    ');

    $view->assertSee('data-notification--toaster-gap-value="10"', false);
    $view->assertSee('data-notification--toaster-hotkey-value="alt+T"', false);
    $view->assertSee('data-notification--toaster-dir-value="rtl"', false);
    $view->assertSee('data-notification--toaster-offset-value="16px"', false);
    $view->assertSee('data-notification--toaster-mobile-offset-value="8px"', false);
    $view->assertSee('data-notification--toaster-class-name-value="my-toast-list"', false);
    $view->assertSee('data-notification--toaster-container-aria-label-value="Notifications"', false);
    $view->assertSee('data-notification--toaster-custom-aria-label-value="Alert"', false);
    $view->assertSee('data-notification--toaster-swipe-directions-value="left,right"', false);
});

// --- Stimulus controller declaration ---

it('declares only the toaster stimulus controller', function () {
    expect(FlashContainer::stimulusControllers())->toBe(['notification--toaster']);
});

// --- Namespace registration ---

it('renders with hotwire:: prefix alias', function () {
    $view = $this->blade('<x-hotwire::flash-container />');

    $view->assertSee('data-controller="notification--toaster"', false);
    $view->assertSee('id="flash-container"', false);
});

it('registers with custom prefix', function () {
    config()->set('hotwire.prefix', 'custom');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->packageBooted();

    expect(Blade::getClassComponentNamespaces())->toHaveKey('custom');
});
