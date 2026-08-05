<?php

use Emaia\LaravelHotwire\Components\Slider;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function shareSliderErrors(array $errorsByKey): void
{
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag($errorsByKey));
    view()->share('errors', $bag);
}

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
    request()->setLaravelSession($this->app['session.store']);
    session()->forget('_old_input');
});

// --- Basic render ---

it('renders a native scalar slider', function () {
    $view = $this->blade('<x-hw::slider name="volume" />');

    $view->assertSee('<input', false)
        ->assertSee('type="range"', false)
        ->assertSee('data-slot="slider"', false)
        ->assertSee('data-orientation="horizontal"', false)
        ->assertSee('data-controller="slider"', false)
        ->assertSee('data-action="input->slider#update"', false)
        ->assertSee('name="volume"', false)
        ->assertSee('id="volume"', false);
});

it('renders numeric configuration including zero and decimals', function () {
    $view = $this->blade('<x-hw::slider name="temperature" :value="0" :min="-10" :max="10" :step="0.5" />');

    $view->assertSee('value="0"', false)
        ->assertSee('min="-10"', false)
        ->assertSee('max="10"', false)
        ->assertSee('step="0.5"', false);
});

it('supports step any', function () {
    $view = $this->blade('<x-hw::slider name="ratio" step="any" />');

    $view->assertSee('step="any"', false);
});

it('leaves native range defaults implicit', function () {
    $view = $this->blade('<x-hw::slider name="volume" />');

    $view->assertDontSee(' value=', false)
        ->assertDontSee(' min=', false)
        ->assertDontSee(' max=', false)
        ->assertDontSee(' step=', false);
});

// --- Server-rendered fill ---

it('paints the initial fill before the controller connects', function () {
    $view = $this->blade('<x-hw::slider name="volume" :value="80" />');

    $view->assertSee('style="--slider-value: 80%;"', false);
});

it('computes the fill from custom bounds', function () {
    $view = $this->blade('<x-hw::slider name="temperature" :value="0" :min="-10" :max="10" />');

    $view->assertSee('--slider-value: 50%', false);
});

it('falls back to the native midpoint when no value is given', function () {
    $view = $this->blade('<x-hw::slider name="volume" />');

    $view->assertSee('--slider-value: 50%', false);
});

it('clamps the fill and tolerates degenerate bounds', function () {
    $over = $this->blade('<x-hw::slider name="volume" :value="500" :max="100" />');
    $under = $this->blade('<x-hw::slider name="volume" :value="-50" :min="0" />');
    $collapsed = $this->blade('<x-hw::slider name="volume" :value="5" :min="10" :max="10" />');

    $over->assertSee('--slider-value: 100%', false);
    $under->assertSee('--slider-value: 0%', false);
    $collapsed->assertSee('--slider-value: 0%', false);
});

it('keeps a user style alongside the fill custom property', function () {
    $view = $this->blade('<x-hw::slider name="volume" :value="20" style="width: 12rem" />');

    $view->assertSee('style="--slider-value: 20%; width: 12rem"', false);
});

it('renders vertical orientation and normalizes invalid values', function () {
    $vertical = $this->blade('<x-hw::slider name="volume" orientation="vertical" />');
    $invalid = $this->blade('<x-hw::slider name="volume" orientation="diagonal" />');

    $vertical->assertSee('data-orientation="vertical"', false)
        ->assertSee('aria-orientation="vertical"', false)
        ->assertDontSee(' orientation=', false);
    $invalid->assertSee('data-orientation="horizontal"', false);
});

it('does not allow the native range type to be overridden', function () {
    $view = $this->blade('<x-hw::slider name="volume" type="text" />');

    $view->assertSee('type="range"', false)
        ->assertDontSee('type="text"', false);
});

it('emits semantic styling hooks without inline package utilities', function () {
    $view = $this->blade('<x-hw::slider name="volume" />');

    $view->assertDontSee('appearance-none', false)
        ->assertDontSee('bg-primary', false)
        ->assertDontSee('rounded-full', false);
});

// --- Id, old input and validation ---

it('derives ids from bracket notation and respects explicit ids', function () {
    $derived = $this->blade('<x-hw::slider name="filters[price]" />');
    $explicit = $this->blade('<x-hw::slider name="price" id="budget" />');

    $derived->assertSee('id="filters-price"', false);
    $explicit->assertSee('id="budget"', false);
});

it('generates an id without rendering an absent name', function () {
    $view = $this->blade('<x-hw::slider />');

    $view->assertSee('id="hw-slider-', false)
        ->assertDontSee(' name=', false);
});

it('restores old input through the derived error key', function () {
    session()->put('_old_input', ['filters' => ['price' => '75']]);

    $view = $this->blade('<x-hw::slider name="filters[price]" value="25" />');

    $view->assertSee('value="75"', false);
});

it('can opt out of old input restoration', function () {
    session()->put('_old_input', ['price' => '75']);

    $view = $this->blade('<x-hw::slider name="price" value="25" :old="false" />');

    $view->assertSee('value="25"', false)
        ->assertDontSee('value="75"', false);
});

it('sets aria validation state from derived and explicit error keys', function () {
    shareSliderErrors([
        'filters.price' => ['Invalid price.'],
        'custom.volume' => ['Invalid volume.'],
    ]);

    $derived = $this->blade('<x-hw::slider name="filters[price]" />');
    $explicit = $this->blade('<x-hw::slider name="volume" error-key="custom.volume" />');

    $derived->assertSee('aria-describedby="filters-price-error"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('data-invalid', false);
    $explicit->assertSee('aria-invalid="true"', false)
        ->assertDontSee('error-key', false);
});

it('inherits the field name without emitting non-applicable required semantics', function () {
    $view = $this->blade('
        <x-hw::field name="volume" required>
            <x-hw::slider />
        </x-hw::field>
    ');

    $view->assertSee('name="volume"', false)
        ->assertSee('id="volume"', false)
        ->assertDontSee(' required', false)
        ->assertDontSee('aria-required', false);
});

// --- Stimulus and auto-submit ---

it('merges user controllers and actions with slider behavior', function () {
    $view = $this->blade('<x-hw::slider name="volume" data-controller="analytics" data-action="change->analytics#track" />');

    $view->assertSee('data-controller="slider analytics"', false)
        ->assertSee('data-action="input->slider#update change->analytics#track"', false);
});

it('merges fluent stimulus attributes', function () {
    $view = $this->blade('<x-hw::slider name="volume" :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'change\')" />');

    $view->assertSee('data-controller="slider analytics"', false)
        ->assertSee('data-action="input->slider#update change->analytics#track"', false);
});

it('protects internal slider and active auto-submit data attributes', function () {
    $view = $this->blade('<x-hw::slider name="volume" auto-submit data-slider-target="override" data-auto-submit-delay-param="10" />');

    $view->assertDontSee('data-slider-target="override"', false)
        ->assertDontSee('data-auto-submit-delay-param="10"', false);
});

it('uses debounced input auto-submit by default', function () {
    $view = $this->blade('<x-hw::slider name="volume" auto-submit auto-submit-delay="600" />');

    $view->assertSee('data-action="input->slider#update input->auto-submit#debouncedSubmit"', false)
        ->assertSee('data-auto-submit-delay-param="600"', false)
        ->assertDontSee(' auto-submit=', false)
        ->assertDontSee(' auto-submit-delay=', false);
});

it('can submit immediately', function () {
    $view = $this->blade('<x-hw::slider name="volume" auto-submit="immediate" />');

    $view->assertSee('data-action="input->slider#update input->auto-submit#submit"', false);
});

it('passes arbitrary native and aria attributes', function () {
    $view = $this->blade('<x-hw::slider name="volume" disabled form="settings" list="ticks" aria-label="Volume" data-test="slider" />');

    $view->assertSee('disabled', false)
        ->assertSee('form="settings"', false)
        ->assertSee('list="ticks"', false)
        ->assertSee('aria-label="Volume"', false)
        ->assertSee('data-test="slider"', false);
});

// --- Catalog ---

it('registers the Slider component and dependencies', function () {
    $slider = HotwireRegistry::make()->component('slider');

    expect($slider->class)->toBe(Slider::class)
        ->and($slider->view)->toBe('hotwire::component-views.slider')
        ->and($slider->docs)->toBe('docs/components/slider.md')
        ->and($slider->controllers)->toBe(['slider', 'auto-submit']);
});
