<?php

use Emaia\LaravelHotwire\Components\Carousel;

// --- optionsJson() ---

it('emits slidesToScroll auto and the reduced-motion breakpoint by default', function () {
    expect(json_decode((new Carousel)->optionsJson(), true))->toBe([
        'slidesToScroll' => 'auto',
        'breakpoints' => ['(prefers-reduced-motion: reduce)' => ['duration' => 0]],
    ]);
});

it('omits Embla defaults (loop, center align, x axis, dragFree, trimSnaps) from the JSON', function () {
    expect(json_decode((new Carousel(respectMotionPreference: false))->optionsJson(), true))
        ->toBe(['slidesToScroll' => 'auto']);
});

it('includes non-default options', function () {
    $json = (new Carousel(
        loop: true,
        align: 'start',
        axis: 'y',
        dragFree: true,
        containScroll: 'keepSnaps',
        respectMotionPreference: false,
    ))->optionsJson();

    expect(json_decode($json, true))->toBe([
        'loop' => true,
        'align' => 'start',
        'axis' => 'y',
        'slidesToScroll' => 'auto',
        'dragFree' => true,
        'containScroll' => 'keepSnaps',
    ]);
});

it('omits slidesToScroll when set to the Embla default of 1', function () {
    expect(json_decode((new Carousel(slidesToScroll: 1, respectMotionPreference: false))->optionsJson(), true))
        ->not->toHaveKey('slidesToScroll');
});

it('merges custom breakpoints with the reduced-motion injection', function () {
    $json = (new Carousel(breakpoints: ['(min-width: 768px)' => ['slidesToScroll' => 3]]))->optionsJson();

    expect(json_decode($json, true)['breakpoints'])->toBe([
        '(min-width: 768px)' => ['slidesToScroll' => 3],
        '(prefers-reduced-motion: reduce)' => ['duration' => 0],
    ]);
});

it('opts out of the reduced-motion breakpoint', function () {
    expect(json_decode((new Carousel(respectMotionPreference: false))->optionsJson(), true))
        ->not->toHaveKey('breakpoints');
});

it('merges the options catch-all', function () {
    $json = (new Carousel(respectMotionPreference: false, options: ['duration' => 30, 'containScroll' => 'keepSnaps']))
        ->optionsJson();
    $decoded = json_decode($json, true);

    expect($decoded['duration'])->toBe(30)
        ->and($decoded['containScroll'])->toBe('keepSnaps');
});

// --- rendering ---

it('renders the controller, viewport, container and slides', function () {
    $view = $this->blade('<x-hwc::carousel><div>one</div><div>two</div></x-hwc::carousel>');

    $view->assertSee('data-controller="carousel"', false);
    $view->assertSee('data-carousel-target="viewport"', false);
    $view->assertSee('data-carousel-target="container"', false);
    $view->assertSee('one');
    $view->assertSee('two');
    $view->assertSee('carousel#teardownForCache', false);
});

it('emits active-dot and disabled-nav class attributes', function () {
    $view = $this->blade('<x-hwc::carousel active-dot-class="is-active" disabled-nav-class="is-disabled">x</x-hwc::carousel>');

    $view->assertSee('data-carousel-active-dot-class="is-active"', false);
    $view->assertSee('data-carousel-disabled-nav-class="is-disabled"', false);
});

it('omits active-dot and disabled-nav data attributes when the prop is empty', function () {
    $view = $this->blade('<x-hwc::carousel>x</x-hwc::carousel>');

    $view->assertDontSee('data-carousel-active-dot-class', false);
    $view->assertDontSee('data-carousel-disabled-nav-class', false);
});

it('renders navigation by default and hides it when disabled', function () {
    $this->blade('<x-hwc::carousel>x</x-hwc::carousel>')->assertSee('data-carousel-target="prevButton"', false);
    $this->blade('<x-hwc::carousel :navigation="false">x</x-hwc::carousel>')
        ->assertDontSee('data-carousel-target="prevButton"', false);
});

it('renders dots by default and hides them when disabled', function () {
    $this->blade('<x-hwc::carousel>x</x-hwc::carousel>')->assertSee('data-carousel-target="dotList"', false);
    $this->blade('<x-hwc::carousel :dots="false">x</x-hwc::carousel>')
        ->assertDontSee('data-carousel-target="dotList"', false);
});

it('emits slide size and spacing as custom properties', function () {
    $view = $this->blade('<x-hwc::carousel slide-size="70%" slide-spacing="1rem">x</x-hwc::carousel>');

    $view->assertSee('--carousel-slide-size: 70%', false);
    $view->assertSee('--carousel-slide-spacing: 1rem', false);
});

it('unions a user data-controller and filters data-carousel-* attributes', function () {
    $view = $this->blade('<x-hwc::carousel data-controller="analytics" data-carousel-foo="bar">x</x-hwc::carousel>');

    $view->assertSee('data-controller="carousel analytics"', false);
    $view->assertDontSee('data-carousel-foo', false);
});

it('wraps a dot_template slot inside the dot button (content slot, like prev/next)', function () {
    $view = $this->blade('
        <x-hwc::carousel>
            <x-slot:dot_template><span class="dot-inner">x</span></x-slot:dot_template>
            <div>slide</div>
        </x-hwc::carousel>
    ');

    // Slot content is kept (not discarded) and the button still carries the action.
    $view->assertSee('dot-inner', false);
    $view->assertSee('data-action="carousel#scrollTo"', false);
});

it('styles and labels the dot list', function () {
    $default = $this->blade('<x-hwc::carousel>x</x-hwc::carousel>');
    $default->assertSee('aria-label="Choose slide"', false);

    $view = $this->blade('<x-hwc::carousel dot-list-class="absolute bottom-3 flex gap-2" dot-list-label="Escolher slide">x</x-hwc::carousel>');
    $view->assertSee('absolute bottom-3 flex gap-2', false);
    $view->assertSee('aria-label="Escolher slide"', false);
});

it('merges prev/next slot attributes onto the buttons (class + aria-label)', function () {
    $view = $this->blade('
        <x-hwc::carousel>
            <x-slot:prev_button class="my-prev" aria-label="Anterior">‹</x-slot:prev_button>
            <div>slide</div>
        </x-hwc::carousel>
    ');

    $view->assertSee('my-prev', false);                 // slot class appended
    $view->assertSee('aria-label="Anterior"', false);   // slot overrides the default label
    $view->assertSee('data-carousel-target="prevButton"', false); // wiring intact
});

it('leaves the nav buttons loose by default (no wrapper)', function () {
    $view = $this->blade('<x-hwc::carousel>x</x-hwc::carousel>');

    $view->assertSee('data-carousel-target="prevButton"', false);
    $view->assertDontSee('data-carousel-nav-wrapper', false);
});

it('wraps the nav buttons when nav-wrapper-class is set', function () {
    $view = $this->blade('<x-hwc::carousel nav-wrapper-class="absolute bottom-3 left-3 flex gap-2">x</x-hwc::carousel>');

    $view->assertSee('data-carousel-nav-wrapper', false);
    $view->assertSee('absolute bottom-3 left-3 flex gap-2', false);
    // buttons still wired inside the wrapper
    $view->assertSee('data-carousel-target="prevButton"', false);
    $view->assertSee('data-carousel-target="nextButton"', false);
});

it('merges dot_template slot attributes onto the dot button', function () {
    $view = $this->blade('
        <x-hwc::carousel>
            <x-slot:dot_template class="h-1 w-6"></x-slot:dot_template>
            <div>slide</div>
        </x-hwc::carousel>
    ');

    $view->assertSee('h-1 w-6', false);
    $view->assertSee('data-action="carousel#scrollTo"', false);
});

it('auto-generates an id and accepts a custom one', function () {
    $this->blade('<x-hwc::carousel>x</x-hwc::carousel>')->assertSee('id="carousel-', false);
    $this->blade('<x-hwc::carousel id="gallery">x</x-hwc::carousel>')->assertSee('id="gallery"', false);
});
