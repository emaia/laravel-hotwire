<?php

use Emaia\LaravelHotwire\Components\ReadMore;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;

it('renders an accessible collapsed read more with first-paint geometry', function () {
    $view = $this->blade('<x-hw::read-more>Long content</x-hw::read-more>');

    $view->assertSee('data-slot="read-more"', false)
        ->assertSee('data-controller="read-more"', false)
        ->assertSee('data-read-more-collapsed-height-value="320"', false)
        ->assertSee('data-read-more-expanded-value="false"', false)
        ->assertSee('data-state="collapsed"', false)
        ->assertSee('--read-more-collapsed-height: 320px', false)
        ->assertSee('data-slot="read-more-viewport"', false)
        ->assertDontSee('data-read-more-viewport', false)
        ->assertSee('data-read-more-target="viewport"', false)
        ->assertSee('data-slot="read-more-content"', false)
        ->assertSee('data-read-more-target="content"', false)
        ->assertSee('tabindex="-1"', false)
        ->assertSee('data-slot="read-more-fade"', false)
        ->assertSee('data-slot="read-more-trigger"', false)
        ->assertSee('data-action="read-more#toggle"', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('aria-controls="hw-read-more-', false)
        ->assertSee('Read more')
        ->assertSee('Read less')
        ->assertSee('Long content');
});

it('renders matching expanded state and accepts its public presentation props', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::read-more
            id="about"
            :collapsed-height="240"
            :expanded="true"
            more-label="Continue reading"
            less-label="Show less"
            icon="arrow-down"
            variant="ghost"
            size="sm"
        >About content</x-hw::read-more>
    BLADE);

    $view->assertSee('id="about"', false)
        ->assertSee('id="about-content"', false)
        ->assertSee('aria-controls="about-content"', false)
        ->assertSee('data-read-more-collapsed-height-value="240"', false)
        ->assertSee('data-read-more-expanded-value="true"', false)
        ->assertSee('data-state="expanded"', false)
        ->assertSee('aria-expanded="true"', false)
        ->assertSee('data-variant="ghost"', false)
        ->assertSee('data-size="sm"', false)
        ->assertSee('Continue reading')
        ->assertSee('Show less')
        ->assertSee('d="M12 5v14"', false);
});

it('supports rich label and trigger icon slots without replacing required wiring', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::read-more>
            <x-slot:more><strong>More details</strong></x-slot:more>
            <x-slot:less><strong>Fewer details</strong></x-slot:less>
            <x-slot:trigger_icon><svg data-test="custom-icon"></svg></x-slot:trigger_icon>
            Content
        </x-hw::read-more>
    BLADE);

    $view->assertSee('<strong>More details</strong>', false)
        ->assertSee('<strong>Fewer details</strong>', false)
        ->assertSee('data-test="custom-icon"', false)
        ->assertSee('data-read-more-target="moreLabel"', false)
        ->assertSee('data-read-more-target="lessLabel"', false)
        ->assertSee('data-read-more-target="icon"', false)
        ->assertSee('data-action="read-more#toggle"', false);
});

it('swaps the controller identifier while keeping structural hooks independent', function () {
    $view = $this->blade('<x-hw::read-more controller="article-preview">Content</x-hw::read-more>');

    $view->assertSee('data-controller="article-preview"', false)
        ->assertSee('data-article-preview-collapsed-height-value="320"', false)
        ->assertSee('data-article-preview-target="viewport"', false)
        ->assertSee('data-action="article-preview#toggle"', false)
        ->assertSee('data-slot="read-more-viewport"', false)
        ->assertDontSee('data-read-more-target', false)
        ->assertDontSee('read-more#toggle', false);
});

it('composes user stimulus attributes and protects its internal state', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::read-more
            class="article-preview"
            style="color: red"
            data-controller="analytics"
            data-action="read-more:change->analytics#track"
            data-read-more-expanded-value="true"
            data-state="static"
            data-ready
            data-transitioning
            data-pinning
            :stimulus="stimulus()->controller('tooltip')->action('tooltip', 'show', 'mouseenter')"
        >Content</x-hw::read-more>
    BLADE);

    $view->assertSee('class="article-preview"', false)
        ->assertSee('color: red', false)
        ->assertSee('--read-more-collapsed-height: 320px', false)
        ->assertSee('data-controller="read-more analytics tooltip"', false)
        ->assertSee('read-more:change->analytics#track mouseenter->tooltip#show', false)
        ->assertSee('data-read-more-expanded-value="false"', false)
        ->assertDontSee('data-read-more-expanded-value="true"', false)
        ->assertSee('data-state="collapsed"', false)
        ->assertDontSee('data-state="static"', false)
        ->assertDontSee('data-ready', false)
        ->assertDontSee('data-transitioning', false)
        ->assertDontSee('data-pinning', false);
});

it('keeps class in the component attribute bag', function () {
    $parameters = collect((new ReflectionClass(ReadMore::class))->getConstructor()?->getParameters())
        ->map->getName()
        ->all();

    expect($parameters)->not->toContain('class');
});

it('registers the read more component and controller dependency', function () {
    $component = HotwireRegistry::make()->component('read-more');

    expect($component)->not->toBeNull()
        ->and($component->class)->toBe(ReadMore::class)
        ->and($component->view)->toBe('hotwire::component-views.read-more')
        ->and($component->docs)->toBe('docs/components/read-more.md')
        ->and($component->category)->toBe('display')
        ->and($component->controllers)->toBe(['read-more']);
});
