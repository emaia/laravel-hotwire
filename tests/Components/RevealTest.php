<?php

use Emaia\LaravelHotwire\Components\Reveal;
use Emaia\LaravelHotwire\Components\Reveal\Item;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Emaia\LaravelHotwire\Support\CssPresetFiles;
use Emaia\LaravelHotwire\Support\RevealContext;
use Emaia\LaravelHotwire\Support\RevealItems;
use Illuminate\Support\Facades\Blade;
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

it('keeps reveal context through an intermediate component with a colliding prop', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::reveal>
            <x-residual-context-wrapper :reveal-counter="(object) ['index' => 99]">
                <x-hw::reveal.item>First</x-hw::reveal.item>
                <x-hw::reveal.item>Second</x-hw::reveal.item>
            </x-residual-context-wrapper>
        </x-hw::reveal>
    BLADE);

    expect($html)
        ->toContain('style="--reveal-index: 0;"')
        ->toContain('style="--reveal-index: 1;"')
        ->not->toContain('style="--reveal-index: 99;"');
});

it('uses independent owners and resumes the outer sequence across nested reveals', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::reveal>
            <x-hw::reveal.item>Outer first</x-hw::reveal.item>
            <x-hw::reveal>
                <x-hw::reveal.item>Inner</x-hw::reveal.item>
            </x-hw::reveal>
            <x-hw::reveal.item>Outer second</x-hw::reveal.item>
        </x-hw::reveal>
    BLADE);

    preg_match_all(
        '/data-slot="reveal-item"[^>]*data-reveal-owner="([0-9]+)"[^>]*style="--reveal-index: ([0-9]+);"/',
        $html,
        $items,
    );

    expect($items[1])->toHaveCount(3)
        ->and($items[1][0])->toBe($items[1][2])
        ->and($items[1][1])->not->toBe($items[1][0])
        ->and($items[2])->toBe(['0', '0', '1']);
});

it('degrades an orphan item to runtime indexing', function () {
    $view = $this->blade('<div data-controller="reveal"><x-hw::reveal.item>Manual item</x-hw::reveal.item></div>');

    $view->assertSee('data-reveal-item', false)
        ->assertSeeText('Manual item')
        ->assertDontSee('data-reveal-owner', false)
        ->assertDontSee('--reveal-index', false);
});

it('degrades through a slot boundary when a wrapper creates the reveal owner', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $html = (string) $this->blade(<<<'BLADE'
        <x-reveal-owner-wrapper>
            <x-hw::reveal.item>Adopted item</x-hw::reveal.item>
        </x-reveal-owner-wrapper>
    BLADE);

    expect($html)
        ->toContain('data-reveal-item')
        ->toContain('Adopted item')
        ->not->toContain('--reveal-index')
        ->toMatch('/<[^>]*data-slot="reveal-item"(?![^>]*data-reveal-owner)[^>]*>Adopted item/s');
});

it('does not expose reveal root props as generic component data', function () {
    $reveal = new Reveal(
        trigger: 'scroll',
        scope: 'document',
        motion: 'flat',
        stagger: '50ms',
        duration: '400ms',
        delay: '100ms',
        maxSteps: 8,
        threshold: 0.25,
        rootMargin: '0px',
        once: false,
        as: 'section',
    );
    $data = $reveal->data();

    expect($data['revealRoot'])->toBe($reveal)
        ->and($data['revealContext'])->toBeInstanceOf(RevealContext::class)
        ->and($data)->not->toHaveKeys([
            'trigger',
            'scope',
            'motion',
            'stagger',
            'duration',
            'delay',
            'maxSteps',
            'threshold',
            'rootMargin',
            'once',
            'as',
            'stimulus',
            'revealCounter',
        ]);
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
    $nova = app(CssPresetFiles::class)->source('nova')->visualCss();

    expect($structural)
        ->toContain('[data-reveal-armed]')
        ->toContain('animation-name: var(--reveal-animation, hotwire-reveal-rise)')
        ->toContain('animation-fill-mode: backwards')
        ->not->toContain('animation: var(--reveal-animation, none)')
        ->toContain('min(var(--reveal-index, 0), var(--reveal-max-steps, 11))')
        ->toContain('html[data-turbo-preview]')
        ->toContain('@media (prefers-reduced-motion: reduce)')
        ->toContain('@keyframes hotwire-reveal-rise')
        ->toContain('@keyframes hotwire-reveal-flat')
        ->toContain('@keyframes hotwire-reveal-fade')
        ->toContain('[data-slot="reveal"][data-motion="flat"]')
        ->toContain('[data-slot="sidebar-container"][data-controller~="reveal"][data-motion="flat"]')
        ->and($nova)
        ->toContain('--reveal-blur: 6px')
        ->toContain('--reveal-shift: 0.75rem')
        ->toContain('@media (width >= 48rem)')
        ->toContain('[data-slot="sidebar"][data-collapsible="icon"] [data-slot="sidebar-group-label"][data-reveal-item]')
        ->toContain('[data-slot="sidebar"][data-collapsible="icon"] [data-reveal-children] > [data-slot="sidebar-group-label"]')
        ->toContain('--reveal-animation: none')
        ->not->toContain('@keyframes hotwire-reveal-');
});

it('keeps direct-children mode when the only raw items belong to a nested reveal', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::reveal>
            <article>First</article>
            <div data-controller="reveal">
                <span data-reveal-item>Belongs to the nested cascade</span>
            </div>
        </x-hw::reveal>
        BLADE);

    // The stylesheet already scopes a nested cascade to its own controller, so counting its items
    // against the outer one only cost the outer its direct children.
    $view->assertSee('data-reveal-children', false);
});

it('releases a component item inherited across a nested manual reveal', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::reveal>
            <article>Outer item</article>
            <div data-controller="reveal">
                <x-hw::reveal.item>Nested manual item</x-hw::reveal.item>
            </div>
        </x-hw::reveal>
    BLADE);

    expect($html)
        ->toContain('data-reveal-children')
        ->toMatch('/<[^>]*data-slot="reveal-item"(?![^>]*data-reveal-owner)(?![^>]*--reveal-index)[^>]*>Nested manual item/s');
});

it('compacts the outer sequence around a nested manual reveal', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::reveal>
            <x-hw::reveal.item>Outer first</x-hw::reveal.item>
            <div data-controller="reveal">
                <x-hw::reveal.item style="color: red">Nested manual item</x-hw::reveal.item>
            </div>
            <x-hw::reveal.item style="color: blue">Outer second</x-hw::reveal.item>
        </x-hw::reveal>
    BLADE);

    expect($html)
        ->toContain('style="--reveal-index: 0;"')
        ->not->toContain('style="--reveal-index: 2;')
        ->toContain('style="color: red;"')
        ->toContain('style="--reveal-index: 1; color: blue;"');
});

it('rewrites real item attributes instead of attribute-like text inside values', function () {
    $source = new RevealContext;
    $target = new RevealContext;
    $html = '<div data-slot="reveal-item" data-reveal-item title=\' data-reveal-owner="999" style="color: red"\' data-reveal-owner="'.$source->owner().'">Item</div>';

    $resolved = RevealItems::resolve($html, $target);
    $scoped = $resolved['html'];

    expect($scoped)
        ->toContain('title=\' data-reveal-owner="999" style="color: red"\'')
        ->toContain('data-reveal-owner="'.$target->owner().'"')
        ->toContain('style="--reveal-index: 0;"')
        ->not->toContain('data-reveal-owner="'.$source->owner().'"')
        ->and($resolved['declaresItems'])->toBeTrue();
});

it('ignores item signatures inside raw-text and nested template content', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::reveal>
            <article>One</article>
            <textarea><div data-reveal-item>Raw text</div></textarea>
            <template>
                <template></template>
                <script>const end = "</template>"</script>
                <div data-reveal-item>Template item</div>
            </template>
            <article>Two</article>
        </x-hw::reveal>
    BLADE);

    expect($html)->toContain('data-reveal-children');
});

it('does not treat custom elements with raw-text name prefixes as inert', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::reveal>
            <script-widget><x-hw::reveal.item>Custom element item</x-hw::reveal.item></script-widget>
        </x-hw::reveal>
    BLADE);

    $view->assertDontSee('data-reveal-children', false)
        ->assertSee('style="--reveal-index: 0;"', false);
});

it('does not end raw-text masking on a closing tag prefix', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::reveal>
            <article>Outer item</article>
            <textarea>before </textarea-extra><div data-reveal-item>Template</div></textarea>
        </x-hw::reveal>
    BLADE);

    $view->assertSee('data-reveal-children', false);
});

it('ignores component item signatures in every opaque HTML element', function (string $html) {
    $source = new RevealContext;
    $context = new RevealContext;
    $signature = '<div data-slot="reveal-item" data-reveal-item data-reveal-owner="'.$source->owner().'">Inert</div>';
    $html = str_replace('{item}', $signature, $html);

    expect(RevealItems::declaresItems($html))->toBeFalse()
        ->and(RevealItems::scopeComponentItems($html, $context))->toBe($html);
})->with([
    'iframe' => '<iframe>{item}</iframe>',
    'noembed' => '<noembed>{item}</noembed>',
    'noframes' => '<noframes>{item}</noframes>',
    'noscript' => '<noscript>{item}</noscript>',
    'script' => '<script>{item}</script>',
    'style' => '<style>{item}</style>',
    'template' => '<template>{item}</template>',
    'textarea' => '<textarea>{item}</textarea>',
    'title' => '<title>{item}</title>',
    'xmp' => '<xmp>{item}</xmp>',
    'plaintext' => '<plaintext>{item}',
]);

it('aligns source tags with decoded case-sensitive XPath slot values', function () {
    $source = new RevealContext;
    $target = new RevealContext;
    $html = '<div data-slot="REVEAL-ITEM" data-reveal-item data-reveal-owner="'.$source->owner().'">Lookalike</div>'
        .'<div data-slot="reveal&#45;item" data-reveal-item data-reveal-owner="'.$source->owner().'">Item</div>';

    $scoped = RevealItems::scopeComponentItems($html, $target);

    expect($scoped)
        ->toContain('data-slot="REVEAL-ITEM" data-reveal-item data-reveal-owner="'.$source->owner().'"')
        ->toContain('data-slot="reveal&#45;item" data-reveal-item data-reveal-owner="'.$target->owner().'"')
        ->toContain('style="--reveal-index: 0;"');
});

it('counts template depth only from structural tags', function (string $inertToken) {
    $html = (string) $this->blade(<<<BLADE
        <x-hw::reveal>
            <article>One</article>
            <article>Two</article>
            <template>
                {$inertToken}
                <div data-reveal-item>Inert</div>
            </template>
        </x-hw::reveal>
    BLADE);

    expect($html)->toContain('data-reveal-children');
})->with([
    'comment' => '<!-- </template> -->',
    'attribute' => '<div title="</template>"></div>',
    'raw text' => '<script>const end = "</template>"</script>',
]);

it('resumes structural scanning after a template closes', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::reveal>
            <template><!-- <template> --></template>
            <div data-reveal-item>Active</div>
        </x-hw::reveal>
    BLADE);

    expect($html)->not->toContain('data-reveal-children');
});

it('keeps structural scanning intact around literal less-than signs and quoted tag text', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::reveal>
            <x-hw::reveal.item>Outer first</x-hw::reveal.item>
            <p>{!! "a < b, it's fine" !!}</p>
            <x-hw::badge title='6" pipe'>Spec</x-hw::badge>
            <div data-controller="reveal">
                <x-hw::reveal.item>Nested manual item</x-hw::reveal.item>
            </div>
            <x-hw::reveal.item>Outer second</x-hw::reveal.item>
        </x-hw::reveal>
    BLADE);

    expect($html)
        ->toContain('style="--reveal-index: 0;"')
        ->toContain('style="--reveal-index: 1;"')
        ->not->toContain('style="--reveal-index: 2;');
});

it('leaves direct-children mode when the slot declares its own raw items', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::reveal>
            <article>Not an item</article>
            <article data-reveal-item>An item</article>
        </x-hw::reveal>
        BLADE);

    $view->assertDontSee('data-reveal-children', false);
});
