<?php

use Emaia\LaravelHotwire\Components\Reveal;
use Emaia\LaravelHotwire\Components\Reveal\Item;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Illuminate\Support\Facades\File;

it('renders direct children as reveal items without per-item markup', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::reveal>
            <article>First</article>
            <article>Second</article>
        </x-hw::reveal>
        BLADE);

    $view->assertSee('data-slot="reveal"', false)
        ->assertSee('data-controller="reveal"', false)
        ->assertSee('data-reveal-children', false)
        ->assertSee('data-reveal-trigger-value="load"', false)
        ->assertSee('data-reveal-scope="render"', false)
        ->assertSee('data-motion="rise"', false)
        ->assertSee('<article>First</article>', false);
});

it('switches to explicit item mode and shares sequential indexes', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::reveal motion="flat">
            <x-hw::reveal.item>First</x-hw::reveal.item>
            <div><x-hw::reveal.item as="section">Second</x-hw::reveal.item></div>
        </x-hw::reveal>
        BLADE);

    expect($html)
        ->not->toContain('data-reveal-children')
        ->toContain('data-motion="flat"')
        ->toContain('data-slot="reveal-item"')
        ->toContain('data-reveal-item')
        ->toContain('style="--reveal-index: 0;"')
        ->toContain('style="--reveal-index: 1;"')
        ->toContain('<section');
});

it('detects raw reveal item markup as explicit mode', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::reveal>
            <form><label data-reveal-item>Title</label></form>
        </x-hw::reveal>
        BLADE);

    $view->assertSee('data-reveal-item', false)
        ->assertDontSee('data-reveal-children', false);
});

it('keeps direct-child mode when only a nested Reveal has explicit items', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::reveal>
            <header>Outer header</header>
            <x-hw::reveal>
                <x-hw::reveal.item>Inner item</x-hw::reveal.item>
            </x-hw::reveal>
        </x-hw::reveal>
        BLADE);

    expect(preg_match_all('/\sdata-reveal-children(?:=|\s|>)/', $html))->toBe(1);
});

it('emits scroll and timing configuration as controller values and custom properties', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::reveal
            trigger="scroll"
            scope="document"
            stagger="55ms"
            duration="440ms"
            delay="110ms"
            :max-steps="7"
            :threshold="0.25"
            root-margin="0px 0px -20% 0px"
            :once="false"
        ><div>Item</div></x-hw::reveal>
        BLADE);

    $view->assertSee('data-reveal-trigger-value="scroll"', false)
        ->assertSee('data-reveal-threshold-value="0.25"', false)
        ->assertSee('data-reveal-root-margin-value="0px 0px -20% 0px"', false)
        ->assertSee('data-reveal-once-value="false"', false)
        ->assertSee('data-reveal-scope="document"', false)
        ->assertSee('--reveal-stagger: 55ms', false)
        ->assertSee('--reveal-duration: 440ms', false)
        ->assertSee('--reveal-delay: 110ms', false)
        ->assertSee('--reveal-max-steps: 7', false);
});

it('composes user Stimulus wiring while protecting Reveal configuration', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::reveal
            data-controller="analytics"
            data-action="reveal:shown->analytics#track"
            data-reveal-trigger-value="scroll"
            data-reveal-state="done"
            :stimulus="stimulus()->controller('tooltip')->action('tooltip', 'show', 'mouseenter')"
        ><div>Item</div></x-hw::reveal>
        BLADE);

    $view->assertSee('data-controller="reveal analytics tooltip"', false)
        ->assertSee('data-action="reveal:shown->analytics#track mouseenter->tooltip#show"', false)
        ->assertSee('data-reveal-trigger-value="load"', false)
        ->assertDontSee('data-reveal-trigger-value="scroll"', false)
        ->assertDontSee('data-reveal-state="done"', false);
});

it('rejects unsupported trigger scope motion and tags', function (Closure $render, string $message) {
    expect($render)->toThrow(InvalidArgumentException::class, $message);
})->with([
    'trigger' => [fn () => new Reveal(trigger: 'hover'), 'Supported values: load, scroll.'],
    'scope' => [fn () => new Reveal(scope: 'visit'), 'Supported values: render, document.'],
    'motion' => [fn () => new Reveal(motion: 'zoom'), 'Supported values: rise, flat, fade.'],
    'root tag' => [fn () => new Reveal(as: 'script'), 'Unsupported reveal tag.'],
    'item tag' => [fn () => new Item(as: 'script'), 'Unsupported reveal item tag.'],
]);

it('registers Reveal components and controller metadata', function () {
    $registry = HotwireRegistry::make();
    $root = $registry->component('reveal');
    $item = $registry->component('reveal.item');
    $controller = $registry->controller('reveal');

    expect($root->class)->toBe(Reveal::class)
        ->and($root->controllers)->toBe(['reveal'])
        ->and($item->class)->toBe(Item::class)
        ->and($controller->source)->toBe('resources/js/controllers/reveal_controller.js')
        ->and($controller->npm)->toBe([])
        ->and(ComponentAliases::subComponents())->toHaveKey('reveal.item');
});

it('ships first-paint mechanics separately from preset motion', function () {
    $structural = File::get(__DIR__.'/../../resources/css/structural.css');
    $nova = File::get(__DIR__.'/../../resources/css/presets/nova.css');

    expect($structural)
        ->toContain('[data-reveal-armed]')
        ->toContain('animation: var(--reveal-animation, none)')
        ->toContain('backwards')
        ->toContain('min(var(--reveal-index, 0), var(--reveal-max-steps, 11))')
        ->toContain('html[data-turbo-preview]')
        ->toContain('@media (prefers-reduced-motion: reduce)')
        ->and($nova)
        ->toContain('[data-slot="reveal"][data-motion="rise"]')
        ->toContain('@keyframes hotwire-reveal-rise')
        ->toContain('@keyframes hotwire-reveal-flat')
        ->toContain('@keyframes hotwire-reveal-fade');
});
