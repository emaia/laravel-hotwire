<?php

use Emaia\LaravelHotwire\Components\Drawer;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

it('renders drawer markup and controller hooks', function () {
    $view = $this->blade('<x-hw::drawer><x-hw::drawer.content>Body</x-hw::drawer.content></x-hw::drawer>');

    $view->assertSee('data-slot="drawer"', false)
        ->assertSee('data-controller="drawer"', false)
        ->assertSee('data-slot="drawer-overlay"', false)
        ->assertSee('data-drawer-target="modal"', false)
        ->assertSee('data-slot="drawer-popup"', false)
        ->assertSee('data-drawer-target="dialog"', false)
        ->assertSee('data-slot="drawer-content"', false)
        ->assertSee('data-direction="down"', false)
        ->assertSee('data-axis="y"', false)
        ->assertSee('data-state="closed"', false)
        ->assertSee('data-motion="default"', false)
        ->assertSee('inert', false)
        ->assertDontSee('data-drawer-hidden-class', false)
        ->assertDontSee('data-drawer-open-duration-value', false)
        ->assertSee('role="dialog"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSeeText('Body');
});

it('renders trigger close and semantic content subcomponents', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::drawer direction="right">
            <x-hw::drawer.trigger>Open</x-hw::drawer.trigger>
            <x-hw::drawer.content>
                <x-hw::drawer.header>
                    <x-hw::drawer.title>Title</x-hw::drawer.title>
                    <x-hw::drawer.description>Description</x-hw::drawer.description>
                </x-hw::drawer.header>
                <x-hw::drawer.footer>
                    <x-hw::drawer.close>Close</x-hw::drawer.close>
                </x-hw::drawer.footer>
            </x-hw::drawer.content>
        </x-hw::drawer>
    BLADE);

    $view->assertSee('data-slot="drawer-trigger"', false)
        ->assertSee('data-action="click-&gt;drawer#toggle"', false)
        ->assertSee('data-direction="right"', false)
        ->assertSee('data-axis="x"', false)
        ->assertSee('data-slot="drawer-header"', false)
        ->assertSee('data-slot="drawer-title"', false)
        ->assertSee('data-slot="drawer-description"', false)
        ->assertSee('data-slot="drawer-footer"', false)
        ->assertSee('data-slot="drawer-close"', false)
        ->assertSee('data-action="drawer#close"', false);
});

it('maps side to semantic direction and size axis', function () {
    $right = $this->blade('<x-hw::drawer direction="right" size="24rem"><x-hw::drawer.content /></x-hw::drawer>');
    $right->assertSee('data-direction="right"', false)
        ->assertSee('--drawer-width: 24rem', false)
        ->assertSee('--drawer-max-width: 24rem', false);

    $bottom = $this->blade('<x-hw::drawer side="bottom" size="50vh"><x-hw::drawer.content /></x-hw::drawer>');
    $bottom->assertSee('data-direction="down"', false)
        ->assertSee('--drawer-height: 50vh', false);
});

it('normalizes drawer motion', function () {
    $none = $this->blade('<x-hw::drawer motion="none"><x-hw::drawer.content /></x-hw::drawer>');
    $invalid = $this->blade('<x-hw::drawer motion="spin"><x-hw::drawer.content /></x-hw::drawer>');

    $none->assertSee('data-motion="none"', false);
    $invalid->assertSee('data-motion="default"', false);
});

it('renders frame content and loading template when frame is configured', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::drawer id="drawer-shell" frame="drawer-frame">
            <x-hw::drawer.content>Fallback</x-hw::drawer.content>
            <x-slot:loading_template>Loading drawer...</x-slot:loading_template>
        </x-hw::drawer>
    BLADE);

    $view->assertSee('<turbo-frame id="drawer-frame" data-drawer-target="dynamicContent"', false)
        ->assertSee('Fallback')
        ->assertSee('<template data-drawer-target="loadingTemplate">', false)
        ->assertSee('Loading drawer...');
});

it('renders a complete frame host when drawer content is omitted', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::drawer frame="drawer-panel" direction="down">
            <x-slot:loading_template>
                <div class="p-6">Loading...</div>
            </x-slot:loading_template>
        </x-hw::drawer>
    BLADE);

    $view->assertSee('data-drawer-target="modal"', false)
        ->assertSee('data-drawer-target="dialog"', false)
        ->assertSee('<turbo-frame id="drawer-panel" data-drawer-target="dynamicContent"', false)
        ->assertSee('<template data-drawer-target="loadingTemplate">', false);
});

it('mounts view transitions on automatic and explicit drawer frame hosts', function (string $template) {
    $view = $this->blade($template);
    $html = (string) $view;
    $xpath = new DOMXPath(dom($html));
    $root = $xpath->query('//*[@data-slot="drawer"]')->item(0);
    $frame = $xpath->query('//turbo-frame[@id="drawer-panel"]')->item(0);

    expect($root->getAttribute('data-controller'))->toBe('drawer custom')
        ->and($root->getAttribute('data-test-id'))->toBe('drawer-root')
        ->and($root->hasAttribute('view-transition'))->toBeFalse()
        ->and($frame->getAttribute('data-controller'))->toBe('turbo--view-transition')
        ->and($frame->getAttribute('data-turbo--view-transition-skip-initial-value'))->toBe('true')
        ->and($frame->getAttribute('data-drawer-target'))->toBe('dynamicContent')
        ->and($frame->getAttribute('data-drawer-frame-owner'))->toBe('drawer-shell')
        ->and(substr_count($html, 'data-controller="turbo--view-transition"'))->toBe(1);
})->with([
    'automatic host' => '<x-hw::drawer id="drawer-shell" frame="drawer-panel" view-transition data-controller="custom" data-test-id="drawer-root" />',
    'explicit host' => '<x-hw::drawer id="drawer-shell" frame="drawer-panel" view-transition data-controller="custom" data-test-id="drawer-root"><x-hw::drawer.content>Fallback</x-hw::drawer.content></x-hw::drawer>',
]);

it('does not mount drawer view transitions by default or without a frame', function (string $template) {
    $view = $this->blade($template);
    $xpath = new DOMXPath(dom((string) $view));
    $root = $xpath->query('//*[@data-slot="drawer"]')->item(0);

    expect((string) $view)->not->toContain('turbo--view-transition')
        ->not->toContain('data-turbo--view-transition-skip-initial-value')
        ->and($root->hasAttribute('view-transition'))->toBeFalse();
})->with([
    'disabled' => '<x-hw::drawer frame="drawer-panel" />',
    'no frame' => '<x-hw::drawer view-transition><x-hw::drawer.content>Content</x-hw::drawer.content></x-hw::drawer>',
]);

it('adds one frame host alongside trigger-only drawer content', function () {
    $view = $this->blade('<x-hw::drawer frame="drawer-panel"><x-hw::drawer.trigger>Open</x-hw::drawer.trigger></x-hw::drawer>');

    expect(substr_count((string) $view, '<turbo-frame'))->toBe(1);
});

it('rejects more than one drawer frame host', function () {
    $this->blade('<x-hw::drawer frame="drawer-panel"><x-hw::drawer.content>One</x-hw::drawer.content><x-hw::drawer.content>Two</x-hw::drawer.content></x-hw::drawer>');
})->throws(ViewException::class, 'A drawer with a frame prop must render exactly one drawer.content host.');

it('does not mistake arbitrary drawer owner metadata for a frame host', function () {
    $view = $this->blade('<x-hw::drawer id="drawer-shell" frame="drawer-panel"><div data-drawer-frame-owner="drawer-shell">Content</div></x-hw::drawer>');

    expect(substr_count((string) $view, '<turbo-frame'))->toBe(1);
});

it('keeps drawer aware context through an intermediate component', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $view = $this->blade(<<<'BLADE'
        <x-hw::drawer id="drawer-shell" direction="right" frame="drawer-panel" :backdrop="false" motion="none" view-transition>
            <x-overlay-context-wrapper
                id="shadow-overlay"
                direction="left"
                axis="y"
                :backdrop="true"
                frame="shadow-frame"
                motion="default"
                :view-transition="false"
            >
                <x-hw::drawer.trigger>Open</x-hw::drawer.trigger>
                <x-hw::drawer.content>Owned content</x-hw::drawer.content>
            </x-overlay-context-wrapper>
        </x-hw::drawer>
    BLADE);

    $html = (string) $view;

    expect($html)->toContain('data-direction="right"')
        ->toContain('data-axis="x"')
        ->toContain('data-motion="none"')
        ->toContain('id="drawer-panel"')
        ->toContain('data-drawer-frame-owner="drawer-shell"')
        ->toContain('turbo--view-transition')
        ->not->toContain('data-direction="left"')
        ->not->toContain('data-axis="y"')
        ->not->toContain('id="shadow-frame"')
        ->not->toContain('data-drawer-frame-owner="shadow-overlay"')
        ->not->toContain('data-slot="drawer-backdrop"')
        ->and(substr_count($html, 'data-slot="drawer-overlay"'))->toBe(1)
        ->and(substr_count($html, '<turbo-frame'))->toBe(1);
});

it('requires drawer content to render inside a drawer root', function () {
    $this->blade('<x-hw::drawer.content>Content</x-hw::drawer.content>');
})
    ->throws(ViewException::class, 'must be rendered inside a Drawer root');

it('renders drawer triggers without requiring an owning drawer root', function () {
    $view = $this->blade('<x-hw::drawer.trigger>Open</x-hw::drawer.trigger>');

    $view->assertSee('data-slot="drawer-trigger"', false)
        ->assertSee('data-action="click-&gt;drawer#toggle"', false);
});

it('rejects an unmanaged turbo frame with the drawer frame id', function () {
    $this->blade('<x-hw::drawer id="drawer-shell" frame="drawer-panel"><turbo-frame id="drawer-panel"></turbo-frame></x-hw::drawer>');
})->throws(ViewException::class, 'A drawer with a frame prop must render exactly one drawer.content host.');

it('keeps nested drawer frame hosts scoped to their owner', function () {
    $view = $this->blade('<x-hw::drawer id="outer" frame="outer-frame"><x-hw::drawer id="inner" frame="inner-frame"></x-hw::drawer></x-hw::drawer>');

    expect(substr_count((string) $view, '<turbo-frame'))->toBe(2);
});

it('rejects matching drawer root and frame ids', function () {
    expect(fn () => new Drawer(id: 'panel', frame: 'panel'))->toThrow(InvalidArgumentException::class);
});

it('throws on an invalid side', function () {
    expect(fn () => new Drawer(side: 'diagonal'))->toThrow(InvalidArgumentException::class);
});

it('keeps view transition as the final positional constructor argument', function () {
    $component = new Drawer('', 'down', null, '', null, true, 'none', true, true, true, null, true);

    expect($component->motion)->toBe('none')
        ->and($component->viewTransition)->toBeTrue();
});

it('does not expose drawer root props as generic component data', function () {
    $data = (new Drawer)->data();

    expect($data)->not->toHaveKeys([
        'id',
        'direction',
        'side',
        'size',
        'frame',
        'backdrop',
        'motion',
        'lockScroll',
        'closeOnEscape',
        'closeOnClickOutside',
        'stimulus',
        'viewTransition',
        'axis',
    ])->and($data)->toHaveKeys([
        'drawerId',
        'drawerDirection',
        'drawerAxis',
        'drawerBackdrop',
        'drawerFrame',
        'drawerMotion',
        'drawerViewTransition',
    ]);
});

it('registers drawer in the component catalog and subcomponent aliases', function () {
    $drawer = HotwireRegistry::make()->component('drawer');

    expect($drawer->key)->toBe('drawer')
        ->and($drawer->controllers)->toBe(['drawer', 'turbo--view-transition'])
        ->and($drawer->docs)->toBe('docs/components/drawer.md');

    expect(ComponentAliases::subComponents())
        ->toHaveKey('drawer.trigger')
        ->toHaveKey('drawer.content')
        ->toHaveKey('drawer.close');
});
