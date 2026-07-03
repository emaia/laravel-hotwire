<?php

it('renders a horizontal separator by default', function () {
    $view = $this->blade('<x-hw::separator />');

    $view->assertSee('data-slot="separator"', false)
        ->assertSee('data-orientation="horizontal"', false)
        ->assertSee('role="separator"', false)
        ->assertDontSee('aria-orientation', false);
});

it('renders a vertical separator with aria orientation', function () {
    $view = $this->blade('<x-hw::separator orientation="vertical" />');

    $view->assertSee('data-orientation="vertical"', false)
        ->assertSee('aria-orientation="vertical"', false);
});

it('passes through attributes without inline package classes', function () {
    $view = $this->blade('<x-hw::separator id="rule" class="my-4" data-test="rule" />');

    $view->assertSee('id="rule"', false)
        ->assertSee('class="my-4"', false)
        ->assertSee('data-test="rule"', false)
        ->assertDontSee('bg-border', false);
});
