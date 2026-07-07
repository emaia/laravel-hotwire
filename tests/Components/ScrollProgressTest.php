<?php

it('renders with default props', function () {
    $view = $this->blade('<x-hw::scroll-progress />');

    $view->assertSee('data-controller="scroll-progress"', false);
    $view->assertSee('data-scroll-progress-throttle-delay-value="15"', false);
    $view->assertSee('data-slot="scroll-progress"', false);
    $view->assertDontSee('fixed top-0 left-0 z-50 h-1 bg-primary', false);
});

it('overrides throttle delay', function () {
    $view = $this->blade('<x-hw::scroll-progress :throttle-delay="50" />');

    $view->assertSee('data-scroll-progress-throttle-delay-value="50"', false);
});

it('merges arbitrary stimulus attributes while protecting internal scroll progress attributes', function () {
    $view = $this->blade('
        <x-hw::scroll-progress
            data-controller="custom"
            data-action="click->custom#run"
            data-scroll-progress-throttle-delay-value="100"
            class="h-2 bg-blue-500"
            data-test-id="progress"
        />
    ');

    $view->assertSee('data-controller="scroll-progress custom"', false);
    $view->assertSee('data-action="click->custom#run"', false);
    $view->assertSee('data-scroll-progress-throttle-delay-value="15"', false);
    $view->assertDontSee('data-scroll-progress-throttle-delay-value="100"', false);
    $view->assertSee('h-2 bg-blue-500', false);
    $view->assertSee('data-test-id="progress"', false);
});

it('merges inline stimulus attributes with the scroll progress controller', function () {
    $view = $this->blade('<x-hw::scroll-progress :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'scroll-progress:change\')" />');

    $view->assertSee('data-controller="scroll-progress analytics"', false);
    $view->assertSee('data-action="scroll-progress:change->analytics#track"', false);
});
