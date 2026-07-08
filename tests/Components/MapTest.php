<?php

use Emaia\LaravelHotwire\Components\Map;

// --- Rendering ---

it('renders a div with the map controller and center data attr', function () {
    $view = $this->blade('<x-hw::map :center="[-23.55, -46.63]" />');

    $view->assertSee('data-controller="map"', false);
    $view->assertSee('data-map-center-value="[-23.55,-46.63]"', false);
    $view->assertSee('data-map-zoom-value="13"', false);
});

it('renders the markers data attr as JSON', function () {
    $view = $this->blade('<x-hw::map :markers="[[-23.55, -46.63, \'São Paulo\']]" />');

    $view->assertSee('data-map-markers-value=', false);
    $view->assertSee('-23.55', false);
    $view->assertSee('São Paulo', false);
});

it('renders the url data attr when url prop is set', function () {
    $view = $this->blade('<x-hw::map url="/api/locations" />');

    $view->assertSee('data-map-url-value="/api/locations"', false);
});

it('omits the scroll-wheel-zoom attr when default (true)', function () {
    $view = $this->blade('<x-hw::map :center="[0, 0]" />');

    $view->assertDontSee('data-map-scroll-wheel-zoom-value', false);
});

it('emits the scroll-wheel-zoom attr only when explicitly disabled', function () {
    $view = $this->blade('<x-hw::map :center="[0, 0]" :scroll-wheel-zoom="false" />');

    $view->assertSee('data-map-scroll-wheel-zoom-value="false"', false);
});

// --- Auto-fit ---

it('emits fit data attr when markers are given without center (auto-detect)', function () {
    $view = $this->blade('<x-hw::map :markers="[[-23.55, -46.63]]" />');

    $view->assertSee('data-map-fit-value="true"', false);
});

it('emits fit data attr when url is given without center (auto-detect)', function () {
    $view = $this->blade('<x-hw::map url="/api/locations" />');

    $view->assertSee('data-map-fit-value="true"', false);
});

it('omits fit data attr when center is provided (auto-detect off)', function () {
    $view = $this->blade('<x-hw::map :center="[0, 0]" :markers="[[-23.55, -46.63]]" />');

    $view->assertDontSee('data-map-fit-value', false);
});

it('explicit :fit="true" overrides the auto-detect when center is set', function () {
    $view = $this->blade('<x-hw::map :center="[0, 0]" :markers="[[-23.55, -46.63]]" :fit="true" />');

    $view->assertSee('data-map-fit-value="true"', false);
});

it('explicit :fit="false" overrides auto-detect when markers are given without center', function () {
    $view = $this->blade('<x-hw::map :markers="[[-23.55, -46.63]]" :fit="false" />');

    $view->assertDontSee('data-map-fit-value', false);
});

// --- Validation ---

it('throws when neither center, markers nor url is provided', function () {
    expect(fn () => new Map)->toThrow(InvalidArgumentException::class);
});

it('does not throw when only center is provided', function () {
    expect(fn () => new Map(center: [0, 0]))->not->toThrow(InvalidArgumentException::class);
});

it('does not throw when only markers is provided', function () {
    expect(fn () => new Map(markers: [[0, 0]]))->not->toThrow(InvalidArgumentException::class);
});

it('does not throw when only url is provided', function () {
    expect(fn () => new Map(url: '/api/locations'))->not->toThrow(InvalidArgumentException::class);
});

// --- Sizing ---

it('emits inline style with the default 400px height and 100% width', function () {
    $view = $this->blade('<x-hw::map :center="[0, 0]" />');

    $view->assertSee('style="width: 100%; height: 400px"', false);
});

it('honors custom height and width props', function () {
    $view = $this->blade('<x-hw::map :center="[0, 0]" height="320px" width="640px" />');

    $view->assertSee('style="width: 640px; height: 320px"', false);
});

// --- Controller swap ---

it('swaps the Stimulus identifier when controller prop is set', function () {
    $view = $this->blade('<x-hw::map :center="[0, 0]" controller="store-locator" />');

    $view->assertSee('data-controller="store-locator"', false);
    $view->assertSee('data-store-locator-center-value=', false);
    $view->assertDontSee('data-controller="map"', false);
});

// --- Attribute forwarding ---

it('forwards extra attributes to the wrapper element', function () {
    $view = $this->blade('<x-hw::map :center="[0, 0]" id="main-map" data-test="x" />');

    $view->assertSee('id="main-map"', false);
    $view->assertSee('data-test="x"', false);
});

it('merges class prop with attributes class', function () {
    $view = $this->blade('<x-hw::map :center="[0, 0]" class="rounded shadow" />');

    $view->assertSee('class="rounded shadow"', false);
});

it('merges user data-controller with the package one', function () {
    $view = $this->blade('<x-hw::map :center="[0, 0]" data-controller="my-extra" />');

    $view->assertSee('data-controller="map my-extra"', false);
});

it('lets subclass data values pass through while filtering owned map values', function () {
    $view = $this->blade('<x-hw::map :center="[0, 0]" controller="store-locator" data-store-locator-delay-value="100" data-store-locator-center-value="hacked" />');

    $view->assertSee('data-store-locator-delay-value="100"', false);
    $view->assertDontSee('hacked', false);
});

it('merges inline stimulus attributes with the package one', function () {
    $view = $this->blade('<x-hw::map :center="[0, 0]" :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'map:ready\')" />');

    $view->assertSee('data-controller="map analytics"', false);
    $view->assertSee('data-action="map:ready->analytics#track"', false);
});
