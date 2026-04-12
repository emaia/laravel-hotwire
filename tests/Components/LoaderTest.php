<?php

it('renders with default props', function () {
    $view = $this->blade('<x-hwc::loader />');

    $view->assertSee('animate-spin', false);
    $view->assertSee('size-5 lg:size-4', false);
    $view->assertSee('aria-busy:block', false);
    $view->assertSee('<svg', false);
});

it('accepts custom size', function () {
    $view = $this->blade('<x-hwc::loader size="size-8" />');

    $view->assertSee('size-8', false);
    $view->assertDontSee('size-5', false);
});

it('accepts custom aria-busy class', function () {
    $view = $this->blade('<x-hwc::loader aria-busy-class="group-aria-busy:block" />');

    $view->assertSee('group-aria-busy:block', false);
    $view->assertDontSee('"aria-busy:block"', false);
});

it('merges extra attributes', function () {
    $view = $this->blade('<x-hwc::loader class="text-blue-500" />');

    $view->assertSee('text-blue-500', false);
    $view->assertSee('animate-spin', false);
});

it('renders using :: namespace syntax', function () {
    $view = $this->blade('<x-hwc::loader />');

    $view->assertSee('animate-spin', false);
});
