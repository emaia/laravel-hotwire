<?php

it('renders with default props', function () {
    $view = $this->blade('<x-hwc::spinner />');

    $view->assertSee('animate-spin', false);
    $view->assertSee('<svg', false);
});

it('merges extra attributes', function () {
    $view = $this->blade('<x-hwc::spinner class="text-blue-500" />');

    $view->assertSee('text-blue-500', false);
    $view->assertSee('animate-spin', false);
});

it('renders using :: namespace syntax', function () {
    $view = $this->blade('<x-hwc::spinner />');

    $view->assertSee('animate-spin', false);
});
