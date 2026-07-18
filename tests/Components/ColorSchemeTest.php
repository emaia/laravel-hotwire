<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;

// --- Script ---

it('renders an inline color scheme script with default configuration', function () {
    $view = $this->blade('<x-hw::color-scheme.script />');

    $view->assertSee('<script', false)
        ->assertSee('hotwire.colorScheme', false)
        ->assertSee('system', false)
        ->assertSee('data-theme', false)
        ->assertSee('data-color-scheme-mode', false)
        ->assertSee('localStorage.getItem', false)
        ->assertSee('prefers-color-scheme: dark', false)
        ->assertSee('document.documentElement.style.colorScheme', false)
        ->assertDontSee(' storage-key=', false);
});

it('renders custom color scheme script configuration', function () {
    $view = $this->blade('<x-hw::color-scheme.script default="dark" storage-key="app.theme" attribute="data-color-scheme" />');

    $view->assertSee('app.theme', false)
        ->assertSee('dark', false)
        ->assertSee('data-color-scheme', false);
});

// --- Toggle ---

it('renders a color scheme toggle button with controller values', function () {
    $view = $this->blade('<x-hw::color-scheme.toggle aria-label="Toggle color scheme" />');

    $view->assertSee('<button', false)
        ->assertSee('type="button"', false)
        ->assertSee('data-slot="color-scheme-toggle"', false)
        ->assertSee('data-controller="color-scheme"', false)
        ->assertSee('data-action="color-scheme#cycle"', false)
        ->assertSee('data-color-scheme-modes-value="light dark system"', false)
        ->assertSee('data-color-scheme-storage-key-value="hotwire.colorScheme"', false)
        ->assertSee('data-color-scheme-default-value="system"', false)
        ->assertSee('data-mode="system"', false)
        ->assertSee('data-scheme="light"', false)
        ->assertSee('aria-label="Toggle color scheme"', false);
});

it('renders color scheme toggle icons for light dark and system', function () {
    $view = $this->blade('<x-hw::color-scheme.toggle />');

    $view->assertSee('data-slot="color-scheme-icon"', false)
        ->assertSee('data-scheme-icon="light"', false)
        ->assertSee('data-scheme-icon="dark"', false)
        ->assertSee('data-mode-icon="system"', false);
});

it('accepts color scheme toggle configuration and tooltip integration', function () {
    $view = $this->blade('<x-hw::color-scheme.toggle variant="ghost" size="sm" modes="dark light" storage-key="app.theme" default="dark" tooltip="Theme" tooltip-side="bottom" />');

    $view->assertSee('data-controller="color-scheme tooltip"', false)
        ->assertSee('data-variant="ghost"', false)
        ->assertSee('data-size="sm"', false)
        ->assertSee('data-color-scheme-modes-value="dark light"', false)
        ->assertSee('data-color-scheme-storage-key-value="app.theme"', false)
        ->assertSee('data-color-scheme-default-value="dark"', false)
        ->assertSee('data-tooltip-content-value="Theme"', false)
        ->assertSee('data-tooltip-side-value="bottom"', false)
        ->assertDontSee(' tooltip="Theme"', false);
});

it('lets color scheme toggle props own internal data attributes', function () {
    $view = $this->blade('<x-hw::color-scheme.toggle storage-key="app.theme" data-color-scheme-storage-key-value="override" data-color-scheme-default-value="light" />');

    $view->assertSee('data-color-scheme-storage-key-value="app.theme"', false)
        ->assertSee('data-color-scheme-default-value="system"', false)
        ->assertDontSee('override', false)
        ->assertDontSee('data-color-scheme-default-value="light"', false);
});

// --- Catalog ---

it('registers color scheme components and controller in the catalog', function () {
    $registry = HotwireRegistry::make();

    expect($registry->component('color-scheme.script'))->not->toBeNull()
        ->and($registry->component('color-scheme.toggle'))->not->toBeNull()
        ->and($registry->controller('color-scheme'))->not->toBeNull()
        ->and(array_map(
            fn ($controller) => $controller->identifier,
            $registry->controllersForComponent('color-scheme.toggle'),
        ))->toBe(['color-scheme', 'tooltip']);
});
