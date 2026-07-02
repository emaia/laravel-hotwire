<?php

it('renders a span with semantic badge attributes by default', function () {
    $view = $this->blade('<x-hwc::badge>New</x-hwc::badge>');

    $view->assertSee('<span', false)
        ->assertSee('data-slot="badge"', false)
        ->assertSee('data-variant="default"', false)
        ->assertSee('</span>', false)
        ->assertSeeText('New');
});

it('renders the requested variant without inline package classes', function () {
    $view = $this->blade('<x-hwc::badge variant="destructive">Failed</x-hwc::badge>');

    $view->assertSee('data-variant="destructive"', false)
        ->assertDontSee('bg-destructive', false)
        ->assertDontSee('inline-flex', false);
});

it('renders as a link via the as prop instead of React-style asChild', function () {
    $view = $this->blade('<x-hwc::badge as="a" href="/issues" variant="outline">Issues</x-hwc::badge>');

    $view->assertSee('<a', false)
        ->assertSee('href="/issues"', false)
        ->assertSee('data-slot="badge"', false)
        ->assertSee('data-variant="outline"', false)
        ->assertSee('</a>', false)
        ->assertDontSee('<span', false);
});

it('passes through arbitrary attributes', function () {
    $view = $this->blade('<x-hwc::badge id="status" class="uppercase" aria-label="Status">Live</x-hwc::badge>');

    $view->assertSee('id="status"', false)
        ->assertSee('class="uppercase"', false)
        ->assertSee('aria-label="Status"', false);
});
