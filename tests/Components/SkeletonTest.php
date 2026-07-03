<?php

it('renders a skeleton with semantic slot', function () {
    $view = $this->blade('<x-hw::skeleton />');

    $view->assertSee('data-slot="skeleton"', false)
        ->assertDontSee('animate-pulse', false)
        ->assertDontSee('bg-muted', false);
});

it('passes through attributes', function () {
    $view = $this->blade('<x-hw::skeleton id="loading" class="h-4 w-full" aria-hidden="true" />');

    $view->assertSee('id="loading"', false)
        ->assertSee('class="h-4 w-full"', false)
        ->assertSee('aria-hidden="true"', false);
});
