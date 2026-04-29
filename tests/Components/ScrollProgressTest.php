<?php

it('renders with default props', function () {
    $view = $this->blade('<x-hwc::scroll-progress />');

    $view->assertSee('data-controller="scroll-progress"', false);
    $view->assertSee('data-scroll-progress-throttle-delay-value="15"', false);
    $view->assertSee('fixed top-0 left-0 z-50 h-1 bg-indigo-500', false);
});

it('overrides throttle delay', function () {
    $view = $this->blade('<x-hwc::scroll-progress :throttle-delay="50" />');

    $view->assertSee('data-scroll-progress-throttle-delay-value="50"', false);
});

it('does not forward scroll progress stimulus attributes from arbitrary attributes', function () {
    $view = $this->blade('
        <x-hwc::scroll-progress
            data-controller="custom"
            data-action="click->custom#run"
            data-scroll-progress-throttle-delay-value="100"
            class="h-2 bg-blue-500"
            data-test-id="progress"
        />
    ');

    $view->assertSee('data-controller="scroll-progress"', false);
    $view->assertSee('data-scroll-progress-throttle-delay-value="15"', false);
    $view->assertDontSee('data-controller="custom"', false);
    $view->assertDontSee('data-action="click-&gt;custom#run"', false);
    $view->assertDontSee('data-scroll-progress-throttle-delay-value="100"', false);
    $view->assertSee('h-2 bg-blue-500', false);
    $view->assertSee('data-test-id="progress"', false);
});
