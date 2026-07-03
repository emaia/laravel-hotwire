<?php

it('renders with default props', function () {
    $view = $this->blade('<x-hw::spinner />');

    $view->assertSee('data-slot="spinner"', false);
    $view->assertDontSee('animate-spin', false);
    $view->assertSee('<svg', false);
});

it('merges extra attributes', function () {
    $view = $this->blade('<x-hw::spinner class="text-blue-500" />');

    $view->assertSee('text-blue-500', false);
    $view->assertSee('data-slot="spinner"', false);
});

it('renders using :: namespace syntax', function () {
    $view = $this->blade('<x-hw::spinner />');

    $view->assertSee('data-slot="spinner"', false);
});
