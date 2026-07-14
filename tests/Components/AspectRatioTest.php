<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;

it('renders an aspect ratio wrapper with a css ratio variable', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::aspect-ratio ratio="16/9" id="hero-media">
            <img src="/cover.jpg" alt="Cover">
        </x-hw::aspect-ratio>
    BLADE);

    $view->assertSee('<div', false)
        ->assertSee('id="hero-media"', false)
        ->assertSee('data-slot="aspect-ratio"', false)
        ->assertSee('style="--ratio: 16/9;"', false)
        ->assertSee('<img src="/cover.jpg" alt="Cover">', false)
        ->assertDontSee('aspect-(', false)
        ->assertDontSee('relative', false);
});

it('derives the ratio from width and height when both are provided', function () {
    $view = $this->blade('<x-hw::aspect-ratio width="4" height="3" />');

    $view->assertSee('data-slot="aspect-ratio"', false)
        ->assertSee('style="--ratio: 4/3;"', false);
});

it('passes through class and appends user supplied inline styles', function () {
    $view = $this->blade('<x-hw::aspect-ratio ratio="1/1" class="rounded-lg" style="overflow: hidden" />');

    $view->assertSee('class="rounded-lg"', false)
        ->assertSee('style="--ratio: 1/1; overflow: hidden"', false);
});

it('registers aspect ratio in the component catalog', function () {
    $aspectRatio = HotwireRegistry::make()->component('aspect-ratio');

    expect($aspectRatio->key)->toBe('aspect-ratio')
        ->and($aspectRatio->controllers)->toBe([])
        ->and($aspectRatio->docs)->toBe('docs/components/aspect-ratio.md');
});
