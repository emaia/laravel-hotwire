<?php

use Emaia\LaravelHotwire\Components\BackToTop;
use Emaia\LaravelHotwire\Registry\Category;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;

it('renders a back to top button with accessible defaults', function () {
    $view = $this->blade('<x-hw::back-to-top />');

    $view->assertSee('<button', false)
        ->assertSee('type="button"', false)
        ->assertSee('data-slot="back-to-top"', false)
        ->assertSee('data-variant="default"', false)
        ->assertSee('data-size="icon-lg"', false)
        ->assertSee('data-controller="back-to-top"', false)
        ->assertSee('data-action="back-to-top#scrollToTop"', false)
        ->assertSee('data-back-to-top-threshold-value="400"', false)
        ->assertSee('data-visible="false"', false)
        ->assertSee('aria-label="Back to top"', false)
        ->assertSee('inert', false)
        ->assertSee('d="m18 15-6-6-6 6"', false)
        ->assertDontSee('fixed end-4 bottom-4', false);
});

it('accepts threshold label icon variant and size props', function () {
    $view = $this->blade('<x-hw::back-to-top :threshold="250" label="Return to start" icon="arrow-up" variant="ghost" size="icon-sm" />');

    $view->assertSee('data-back-to-top-threshold-value="250"', false)
        ->assertSee('aria-label="Return to start"', false)
        ->assertSee('data-variant="ghost"', false)
        ->assertSee('data-size="icon-sm"', false)
        ->assertSee('d="m5 12 7-7 7 7"', false)
        ->assertDontSee('d="m18 15-6-6-6 6"', false)
        ->assertDontSee(' threshold=', false)
        ->assertDontSee(' label=', false)
        ->assertDontSee(' icon=', false);
});

it('uses custom slot content instead of the configured icon', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::back-to-top icon="arrow-up">
            <svg data-test="custom-back-to-top-icon"></svg>
        </x-hw::back-to-top>
    BLADE);

    $view->assertSee('data-test="custom-back-to-top-icon"', false)
        ->assertDontSee('d="m5 12 7-7 7 7"', false)
        ->assertDontSee('d="m18 15-6-6-6 6"', false);
});

it('passes through arbitrary html attributes classes and styles', function () {
    $view = $this->blade('<x-hw::back-to-top id="page-top" class="custom-class" style="margin: 1rem" data-track="navigation" aria-describedby="back-to-top-help" />');

    $view->assertSee('id="page-top"', false)
        ->assertSee('class="custom-class"', false)
        ->assertSee('style="margin: 1rem"', false)
        ->assertSee('data-track="navigation"', false)
        ->assertSee('aria-describedby="back-to-top-help"', false);
});

it('composes stimulus attributes while preserving its internal contract', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::back-to-top
            :threshold="250"
            type="submit"
            data-slot="override"
            data-variant="destructive"
            data-size="xs"
            data-controller="analytics"
            data-action="click->analytics#track"
            data-back-to-top-threshold-value="900"
            data-visible="true"
            :inert="false"
            :stimulus="stimulus()->controller('back-to-top', ['threshold' => 700])->controller('tooltip')->action('tooltip', 'show', 'mouseenter')"
        />
    BLADE);

    $view->assertSee('type="button"', false)
        ->assertSee('data-slot="back-to-top"', false)
        ->assertSee('data-variant="default"', false)
        ->assertSee('data-size="icon-lg"', false)
        ->assertSee('data-controller="back-to-top analytics tooltip"', false)
        ->assertSee('data-action="back-to-top#scrollToTop click->analytics#track mouseenter->tooltip#show"', false)
        ->assertSee('data-back-to-top-threshold-value="250"', false)
        ->assertDontSee('data-back-to-top-threshold-value="900"', false)
        ->assertDontSee('data-back-to-top-threshold-value="700"', false)
        ->assertSee('data-visible="false"', false)
        ->assertDontSee('data-visible="true"', false)
        ->assertSee('inert', false);
});

it('registers the back to top component and controller dependency', function () {
    $component = HotwireRegistry::make()->component('back-to-top');

    expect($component)->not->toBeNull()
        ->and($component->class)->toBe(BackToTop::class)
        ->and($component->view)->toBe('hotwire::component-views.back-to-top')
        ->and($component->docs)->toBe('docs/components/back-to-top.md')
        ->and($component->category)->toBe(Category::Utility)
        ->and($component->controllers)->toBe(['back-to-top']);
});
