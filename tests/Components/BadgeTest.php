<?php

it('renders a span with semantic badge attributes by default', function () {
    $view = $this->blade('<x-hw::badge>New</x-hw::badge>');

    $view->assertSee('<span', false)
        ->assertSee('data-slot="badge"', false)
        ->assertSee('data-variant="default"', false)
        ->assertSee('</span>', false)
        ->assertSeeText('New');
});

it('renders the requested variant without inline package classes', function () {
    $view = $this->blade('<x-hw::badge variant="destructive">Failed</x-hw::badge>');

    $view->assertSee('data-variant="destructive"', false)
        ->assertDontSee('bg-destructive', false)
        ->assertDontSee('inline-flex', false);
});

it('renders as a link via the as prop instead of React-style asChild', function () {
    $view = $this->blade('<x-hw::badge as="a" href="/issues" variant="outline">Issues</x-hw::badge>');

    $view->assertSee('<a', false)
        ->assertSee('href="/issues"', false)
        ->assertSee('data-slot="badge"', false)
        ->assertSee('data-variant="outline"', false)
        ->assertSee('</a>', false)
        ->assertDontSee('<span', false);
});

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hw::badge id="status" class="uppercase" aria-label="Status">Live</x-hw::badge>');

    $view->assertSee('id="status"', false)
        ->assertSee('class="uppercase"', false)
        ->assertSee('aria-label="Status"', false);
});
