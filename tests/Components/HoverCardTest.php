<?php

use Emaia\LaravelHotwire\Components\HoverCard;
use Emaia\LaravelHotwire\Components\HoverCard\Content as HoverCardContent;
use Emaia\LaravelHotwire\Components\HoverCard\Trigger as HoverCardTrigger;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Emaia\LaravelHotwire\Support\FieldContext;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

it('keeps href-less anchors keyboard focusable', function () {
    $view = $this->blade('<x-hw::hover-card><x-hw::hover-card.trigger as="a">Profile</x-hw::hover-card.trigger><x-hw::hover-card.content>Details</x-hw::hover-card.content></x-hw::hover-card>');

    $view->assertSee('tabindex="0"', false);
});

it('preserves an explicit hover card trigger tabindex', function () {
    $view = $this->blade('<x-hw::hover-card><x-hw::hover-card.trigger as="a" tabindex="2">Profile</x-hw::hover-card.trigger><x-hw::hover-card.content>Details</x-hw::hover-card.content></x-hw::hover-card>');

    $view->assertSee('tabindex="2"', false);
});

it('uses the focus fallback when tabindex is bound to null', function () {
    $view = $this->blade('<x-hw::hover-card><x-hw::hover-card.trigger as="a" :tabindex="$tabindex">Profile</x-hw::hover-card.trigger><x-hw::hover-card.content>Details</x-hw::hover-card.content></x-hw::hover-card>', ['tabindex' => null]);

    $view->assertSee('tabindex="0"', false);
});

it('removes actions from disabled hover card anchors', function () {
    $view = $this->blade('<x-hw::hover-card><x-hw::hover-card.trigger as="a" href="/users/1" disabled>Profile</x-hw::hover-card.trigger><x-hw::hover-card.content>Details</x-hw::hover-card.content></x-hw::hover-card>');

    preg_match('/<a[^>]*data-slot="hover-card-trigger"[^>]*>/', (string) $view, $trigger);

    expect($trigger[0] ?? '')
        ->not->toContain('data-action=')
        ->not->toContain('href=')
        ->toContain('aria-disabled="true"');
});

it('treats a null disabled binding on hover card anchors as enabled', function () {
    $view = $this->blade('<x-hw::hover-card><x-hw::hover-card.trigger as="a" href="/users/1" :disabled="$disabled">Profile</x-hw::hover-card.trigger><x-hw::hover-card.content>Details</x-hw::hover-card.content></x-hw::hover-card>', ['disabled' => null]);

    preg_match('/<a[^>]*data-slot="hover-card-trigger"[^>]*>/', (string) $view, $trigger);

    expect($trigger[0] ?? '')
        ->toContain('data-action=')
        ->toContain('href="/users/1"')
        ->not->toContain('aria-disabled="true"');
});

it('renders hover card controller, trigger and content wiring', function () {
    $view = $this->blade('
        <x-hw::hover-card>
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content><p>Preview content</p></x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('data-slot="hover-card"', false)
        ->assertSee('data-controller="hover-card"', false)
        ->assertSee('<button type="button"', false)
        ->assertSee('data-slot="hover-card-trigger"', false)
        ->assertSee('data-variant="link"', false)
        ->assertSee('data-size="default"', false)
        ->assertSee('data-hover-card-target="trigger"', false)
        ->assertSee('mouseenter->hover-card#pointerEnter', false)
        ->assertSee('focusin->hover-card#focusIn', false)
        ->assertSee('aria-describedby="hw-hover-card-', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('data-hover-card-state="closed"', false)
        ->assertDontSee('tabindex="0"', false)
        ->assertSee('data-slot="hover-card-content"', false)
        ->assertSee('data-hover-card-target="content"', false)
        ->assertSee('data-state="closed"', false)
        ->assertSee('data-motion="default"', false)
        ->assertSee('hidden', false)
        ->assertSee('inert', false)
        ->assertSee('role="tooltip"', false)
        ->assertSee('User')
        ->assertSee('Preview content');
});

it('links the trigger and content via id and aria-describedby', function () {
    $view = $this->blade('
        <x-hw::hover-card id="user-preview">
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('id="user-preview"', false)
        ->assertSee('aria-describedby="user-preview"', false);
});

it('emits delay and positioning defaults for Floating UI', function () {
    $view = $this->blade('
        <x-hw::hover-card>
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('data-hover-card-open-delay-value="10"', false)
        ->assertSee('data-hover-card-close-delay-value="100"', false)
        ->assertSee('data-hover-card-side-value="bottom"', false)
        ->assertSee('data-hover-card-align-value="start"', false)
        ->assertSee('data-hover-card-side-offset-value="4"', false)
        ->assertSee('data-hover-card-align-offset-value="0"', false)
        ->assertSee('data-hover-card-strategy-value="fixed"', false)
        ->assertSee('data-hover-card-flip-value="true"', false)
        ->assertSee('data-hover-card-shift-value="true"', false)
        ->assertSee('data-side="bottom"', false)
        ->assertSee('data-align="start"', false);
});

it('emits custom delay and positioning values', function () {
    $view = $this->blade('
        <x-hw::hover-card :open-delay="50" :close-delay="25" side="right" align="end" :side-offset="12" :align-offset="-4" strategy="absolute" :flip="false" :shift="false">
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('data-hover-card-open-delay-value="50"', false)
        ->assertSee('data-hover-card-close-delay-value="25"', false)
        ->assertSee('data-hover-card-side-value="right"', false)
        ->assertSee('data-hover-card-align-value="end"', false)
        ->assertSee('data-hover-card-side-offset-value="12"', false)
        ->assertSee('data-hover-card-align-offset-value="-4"', false)
        ->assertSee('data-hover-card-strategy-value="absolute"', false)
        ->assertSee('data-hover-card-flip-value="false"', false)
        ->assertSee('data-hover-card-shift-value="false"', false)
        ->assertSee('data-side="right"', false)
        ->assertSee('data-align="end"', false);
});

it('starts open when open is true', function () {
    $view = $this->blade('
        <x-hw::hover-card :open="true">
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $xpath = new DOMXPath(dom((string) $view));
    $trigger = $xpath->query('//*[@data-hover-card-target="trigger"]')->item(0);
    $content = $xpath->query('//*[@data-hover-card-target="content"]')->item(0);

    expect((string) $view)->toContain('data-hover-card-open-value="true"')
        ->and($trigger?->getAttribute('aria-expanded'))->toBe('true')
        ->and($trigger?->getAttribute('data-hover-card-state'))->toBe('open')
        ->and($content?->getAttribute('data-state'))->toBe('closed')
        ->and($content?->hasAttribute('hidden'))->toBeTrue()
        ->and($content?->hasAttribute('inert'))->toBeTrue();
});

it('does not overwrite a trigger state owned by another behavior', function () {
    $view = $this->blade('
        <x-hw::hover-card>
            <x-hw::hover-card.trigger data-state="on">User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $xpath = new DOMXPath(dom((string) $view));
    $trigger = $xpath->query('//*[@data-hover-card-target="trigger"]')->item(0);

    expect($trigger?->getAttribute('data-state'))->toBe('on')
        ->and($trigger?->getAttribute('data-hover-card-state'))->toBe('closed');
});

it('keeps hover card aware context through an intermediate component', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $view = $this->blade(<<<'BLADE'
        <x-hw::hover-card id="owner-card" :open="true" side="right" align="end">
            <x-overlay-context-wrapper id="shadow-card" :open="false" side="left" align="start">
                <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
                <x-hw::hover-card.content>Owned content</x-hw::hover-card.content>
            </x-overlay-context-wrapper>
        </x-hw::hover-card>
    BLADE);

    $html = (string) $view;

    expect($html)->toContain('aria-describedby="owner-card"')
        ->toContain('id="owner-card"')
        ->toContain('aria-expanded="true"')
        ->toContain('data-hover-card-state="open"')
        ->toContain('data-side="right"')
        ->toContain('data-align="end"')
        ->not->toContain('aria-describedby="shadow-card"')
        ->not->toContain('id="shadow-card"')
        ->not->toContain('data-side="left"')
        ->and(substr_count($html, 'data-slot="hover-card-content"'))->toBe(1);
});

it('requires hover card trigger and content to render inside a hover card root', function (string $component) {
    $tag = $component === 'trigger' ? 'x-hw::hover-card.trigger' : 'x-hw::hover-card.content';

    $this->blade("<{$tag}>Content</{$tag}>");
})->with(['trigger', 'content'])
    ->throws(ViewException::class, 'must be rendered inside a Hover Card root');

it('requires a hover card root even when explicit wiring is supplied', function (string $template) {
    $this->blade($template);
})->with([
    'trigger' => '<x-hw::hover-card.trigger aria-describedby="external-card">Open</x-hw::hover-card.trigger>',
    'content' => '<x-hw::hover-card.content id="external-card">Content</x-hw::hover-card.content>',
])->throws(ViewException::class, 'must be rendered inside a Hover Card root');

it('does not expose hover card root props as generic component data', function () {
    $data = (new HoverCard)->data();
    $frameworkKeys = ['componentName', 'attributes', 'ignoredParameterNames'];
    $genericKeys = array_values(array_filter(
        array_keys($data),
        fn (string $key) => ! str_starts_with($key, 'hoverCard') && ! in_array($key, [...$frameworkKeys, ...array_keys(FieldContext::boundaryData())], true),
    ));

    expect($genericKeys)->toBe([])
        ->and($data)->toHaveKeys([
            'hoverCardId',
            'hoverCardAlign',
            'hoverCardSide',
            'hoverCardSideOffset',
            'hoverCardAlignOffset',
            'hoverCardStrategy',
            'hoverCardFlip',
            'hoverCardShift',
            'hoverCardOpenDelay',
            'hoverCardCloseDelay',
            'hoverCardOpen',
            'hoverCardStimulus',
        ])
        ->and($data)->toHaveKey('fieldContext', null)
        ->toHaveKey('fieldControlContext', null);
});

it('does not expose hover card trigger props as generic component data', function () {
    $data = (new HoverCardTrigger)->data();
    $frameworkKeys = ['componentName', 'attributes', 'ignoredParameterNames'];
    $genericKeys = array_values(array_filter(
        array_keys($data),
        fn (string $key) => ! str_starts_with($key, 'hoverCardTrigger') && ! in_array($key, $frameworkKeys, true),
    ));

    expect($genericKeys)->toBe([])
        ->and($data)->toHaveKeys([
            'hoverCardTriggerAs',
            'hoverCardTriggerVariant',
            'hoverCardTriggerSize',
            'hoverCardTriggerType',
        ]);
});

it('does not expose hover card content props as generic component data', function () {
    $data = (new HoverCardContent)->data();
    $frameworkKeys = ['componentName', 'attributes', 'ignoredParameterNames'];
    $genericKeys = array_values(array_filter(
        array_keys($data),
        fn (string $key) => ! str_starts_with($key, 'hoverCardContent') && ! in_array($key, $frameworkKeys, true),
    ));

    expect($genericKeys)->toBe([])
        ->and($data)->toHaveKey('hoverCardContentMotion');
});

it('renders configurable trigger elements, variants and sizes', function () {
    $view = $this->blade('
        <x-hw::hover-card>
            <x-hw::hover-card.trigger as="a" href="/users/1" variant="ghost" size="sm">Jane Doe</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('<a data-slot="hover-card-trigger"', false)
        ->assertSee('data-variant="ghost"', false)
        ->assertSee('data-size="sm"', false)
        ->assertSee('href="/users/1"', false)
        ->assertSee('</a>', false)
        ->assertDontSee('type="button"', false)
        ->assertDontSee('tabindex="0"', false);
});

it('uses semantic motion values without transition class attributes', function () {
    $on = $this->blade('
        <x-hw::hover-card>
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $on->assertSee('data-motion="default"', false)
        ->assertDontSee('data-transition-', false);

    $off = $this->blade('
        <x-hw::hover-card>
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content motion="none">Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $off->assertSee('data-motion="none"', false)
        ->assertDontSee('data-transition-', false);
});

it('merges stimulus attributes and filters hover card owned data attributes', function () {
    $view = $this->blade('
        <x-hw::hover-card data-controller="analytics" data-hover-card-side-value="top" :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'hover-card:opened\')">
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('data-controller="hover-card analytics"', false)
        ->assertSee('data-action="hover-card:opened->analytics#track"', false)
        ->assertSee('data-hover-card-side-value="bottom"', false)
        ->assertDontSee('data-hover-card-side-value="top"', false);
});

it('keeps hover card owned trigger and content wiring protected', function () {
    $view = $this->blade('
        <x-hw::hover-card id="protected-hover-card" side="right">
            <x-hw::hover-card.trigger data-action="analytics#track" data-hover-card-target="wrong" aria-expanded="manual">
                User
            </x-hw::hover-card.trigger>

            <x-hw::hover-card.content id="wrong" data-state="manual" data-motion="manual" data-side="top" data-hover-card-target="wrong">
                Content
            </x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('analytics#track', false)
        ->assertSee('data-hover-card-target="trigger"', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('id="protected-hover-card"', false)
        ->assertSee('data-state="closed"', false)
        ->assertSee('data-motion="default"', false)
        ->assertSee('data-side="right"', false)
        ->assertSee('data-hover-card-target="content"', false)
        ->assertDontSee('data-hover-card-target="wrong"', false)
        ->assertDontSee('aria-expanded="manual"', false)
        ->assertDontSee('id="wrong"', false)
        ->assertDontSee('data-state="manual"', false)
        ->assertDontSee('data-motion="manual"', false)
        ->assertDontSee('data-side="top"', false);
});

it('registers hover card subcomponent aliases', function () {
    expect(ComponentAliases::subComponents())
        ->toHaveKey('hover-card.trigger')
        ->toHaveKey('hover-card.content');
});

it('registers hover card in the catalog with its controller dependency', function () {
    $registry = HotwireRegistry::make();

    expect($registry->component('hover-card')->controllers)->toBe(['hover-card'])
        ->and($registry->controller('hover-card')->npm)->toHaveKey('@floating-ui/dom', '^1.8.0');
});
