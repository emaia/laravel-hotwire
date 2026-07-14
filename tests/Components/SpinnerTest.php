<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;

it('renders with default props', function () {
    $view = $this->blade('<x-hw::spinner />');

    $view->assertSee('data-slot="spinner"', false);
    $view->assertSee('role="status"', false);
    $view->assertSee('aria-label="Loading"', false);
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

it('registers spinner in the component catalog', function () {
    $spinner = HotwireRegistry::make()->component('spinner');

    expect($spinner->key)->toBe('spinner')
        ->and($spinner->category)->toBe('feedback')
        ->and($spinner->controllers)->toBe([])
        ->and($spinner->docs)->toBe('docs/components/spinner.md');
});
