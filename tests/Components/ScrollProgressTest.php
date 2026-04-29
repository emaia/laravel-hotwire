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

it('merges arbitrary attributes without replacing the controller', function () {
    $view = $this->blade('<x-hwc::scroll-progress data-controller="custom" class="h-2 bg-blue-500" data-test-id="progress" />');

    $view->assertSee('data-controller="scroll-progress"', false);
    $view->assertDontSee('data-controller="custom"', false);
    $view->assertSee('h-2 bg-blue-500', false);
    $view->assertSee('data-test-id="progress"', false);
});
