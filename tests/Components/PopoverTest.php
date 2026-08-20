<?php

use Emaia\LaravelHotwire\Components\Popover;
use Emaia\LaravelHotwire\Components\Popover\Content as PopoverContent;
use Emaia\LaravelHotwire\Components\Popover\Description as PopoverDescription;
use Emaia\LaravelHotwire\Components\Popover\Header as PopoverHeader;
use Emaia\LaravelHotwire\Components\Popover\Title as PopoverTitle;
use Emaia\LaravelHotwire\Components\Popover\Trigger as PopoverTrigger;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

it('renders popover controller, trigger and content wiring', function () {
    $view = $this->blade('
        <x-hw::popover>
            <x-hw::popover.trigger>Details</x-hw::popover.trigger>
            <x-hw::popover.content><p>Panel content</p></x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-slot="popover"', false)
        ->assertSee('data-controller="popover"', false)
        ->assertSee('data-slot="popover-trigger"', false)
        ->assertSee('data-popover-target="trigger"', false)
        ->assertSee('data-action="popover#toggle"', false)
        ->assertSee('aria-haspopup="dialog"', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('data-popover-state="closed"', false)
        ->assertSee('data-slot="popover-content"', false)
        ->assertSee('data-popover-target="content"', false)
        ->assertSee('data-state="closed"', false)
        ->assertSee('data-motion="default"', false)
        ->assertSee('hidden', false)
        ->assertSee('inert', false)
        ->assertSee('role="dialog"', false)
        ->assertSee('tabindex="-1"', false)
        ->assertSee('Details')
        ->assertSee('Panel content');
});

it('links the trigger and content via id and aria-controls', function () {
    $view = $this->blade('
        <x-hw::popover id="account-popover">
            <x-hw::popover.trigger>Account</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('id="account-popover"', false)
        ->assertSee('aria-controls="account-popover"', false);
});

it('auto-generates a content id when none is given', function () {
    $view = $this->blade('
        <x-hw::popover>
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('id="popover-', false)
        ->assertSee('aria-controls="popover-', false);
});

it('emits positioning defaults for Floating UI', function () {
    $view = $this->blade('
        <x-hw::popover>
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-popover-side-value="bottom"', false)
        ->assertSee('data-popover-align-value="start"', false)
        ->assertSee('data-popover-side-offset-value="4"', false)
        ->assertSee('data-popover-align-offset-value="0"', false)
        ->assertSee('data-popover-strategy-value="fixed"', false)
        ->assertSee('data-popover-flip-value="true"', false)
        ->assertSee('data-popover-shift-value="true"', false)
        ->assertSee('data-side="bottom"', false)
        ->assertSee('data-align="start"', false);
});

it('emits custom positioning values', function () {
    $view = $this->blade('
        <x-hw::popover side="right" align="end" :side-offset="12" :align-offset="-4" strategy="absolute" :flip="false" :shift="false">
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-popover-side-value="right"', false)
        ->assertSee('data-popover-align-value="end"', false)
        ->assertSee('data-popover-side-offset-value="12"', false)
        ->assertSee('data-popover-align-offset-value="-4"', false)
        ->assertSee('data-popover-strategy-value="absolute"', false)
        ->assertSee('data-popover-flip-value="false"', false)
        ->assertSee('data-popover-shift-value="false"', false)
        ->assertSee('data-side="right"', false)
        ->assertSee('data-align="end"', false);
});

it('starts open when open is true', function () {
    $view = $this->blade('
        <x-hw::popover :open="true">
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $xpath = new DOMXPath(dom((string) $view));
    $trigger = $xpath->query('//*[@data-popover-target="trigger"]')->item(0);
    $content = $xpath->query('//*[@data-popover-target="content"]')->item(0);

    expect((string) $view)->toContain('data-popover-open-value="true"')
        ->and($trigger?->getAttribute('aria-expanded'))->toBe('true')
        ->and($trigger?->getAttribute('data-popover-state'))->toBe('open')
        ->and($content?->getAttribute('data-state'))->toBe('closed')
        ->and($content?->hasAttribute('hidden'))->toBeTrue()
        ->and($content?->hasAttribute('inert'))->toBeTrue();
});

it('does not overwrite a trigger state owned by another behavior', function () {
    $view = $this->blade('
        <x-hw::popover>
            <x-hw::popover.trigger data-state="on">Open</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $xpath = new DOMXPath(dom((string) $view));
    $trigger = $xpath->query('//*[@data-popover-target="trigger"]')->item(0);

    expect($trigger?->getAttribute('data-state'))->toBe('on')
        ->and($trigger?->getAttribute('data-popover-state'))->toBe('closed');
});

it('keeps popover aware context through an intermediate component', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $view = $this->blade(<<<'BLADE'
        <x-hw::popover id="owner-popover" :open="true" side="right" align="end">
            <x-overlay-context-wrapper id="shadow-popover" :open="false" side="left" align="start">
                <x-hw::popover.trigger>Open</x-hw::popover.trigger>
                <x-hw::popover.content>Owned content</x-hw::popover.content>
            </x-overlay-context-wrapper>
        </x-hw::popover>
    BLADE);

    $html = (string) $view;

    expect($html)->toContain('aria-controls="owner-popover"')
        ->toContain('id="owner-popover"')
        ->toContain('aria-expanded="true"')
        ->toContain('data-popover-state="open"')
        ->toContain('data-side="right"')
        ->toContain('data-align="end"')
        ->not->toContain('aria-controls="shadow-popover"')
        ->not->toContain('id="shadow-popover"')
        ->not->toContain('data-side="left"')
        ->and(substr_count($html, 'data-slot="popover-content"'))->toBe(1);
});

it('requires popover trigger and content to render inside a popover root', function (string $component) {
    $tag = $component === 'trigger' ? 'x-hw::popover.trigger' : 'x-hw::popover.content';

    $this->blade("<{$tag}>Content</{$tag}>");
})->with(['trigger', 'content'])
    ->throws(ViewException::class, 'must be rendered inside a Popover root');

it('renders standalone popover subcomponents when owner wiring is explicit', function () {
    $trigger = $this->blade('<x-hw::popover.trigger standalone aria-controls="external-popover">Open</x-hw::popover.trigger>');
    $content = $this->blade('<x-hw::popover.content standalone id="external-popover" side="top" align="end" motion="none">Content</x-hw::popover.content>');

    $trigger->assertSee('aria-controls="external-popover"', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertDontSee('data-popover-target="trigger"', false);
    $content->assertSee('id="external-popover"', false)
        ->assertDontSee('data-popover-target="content"', false)
        ->assertSee('data-side="top"', false)
        ->assertSee('data-align="end"', false)
        ->assertSee('data-motion="none"', false);
});

it('keeps explicit standalone popover wiring inside another popover root', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::popover id="outer-popover" side="bottom" align="start">
            <x-hw::popover.trigger standalone aria-controls="inner-popover">Inner trigger</x-hw::popover.trigger>
            <x-hw::popover.content standalone id="inner-popover" side="top" align="end">Inner content</x-hw::popover.content>
            <x-hw::popover.content>Outer content</x-hw::popover.content>
        </x-hw::popover>
    BLADE);

    $html = (string) $view;

    expect($html)->toContain('aria-controls="inner-popover"')
        ->toContain('id="inner-popover"')
        ->toContain('data-side="top"')
        ->toContain('data-align="end"')
        ->and(substr_count($html, 'id="outer-popover"'))->toBe(1)
        ->and(substr_count($html, 'data-popover-target="trigger"'))->toBe(0)
        ->and(substr_count($html, 'data-popover-target="content"'))->toBe(1);
});

it('falls back to popover root placement for standalone content without explicit placement', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::popover id="outer-popover" side="top" align="end">
            <x-hw::popover.content standalone id="inner-popover">Inner content</x-hw::popover.content>
            <x-hw::popover.content>Outer content</x-hw::popover.content>
        </x-hw::popover>
    BLADE);

    $html = (string) $view;

    expect($html)->toContain('id="inner-popover"')
        ->toContain('data-side="top"')
        ->toContain('data-align="end"');
});

it('requires explicit popover wiring when standalone is used inside a popover root', function (string $template) {
    $this->blade($template);
})->with([
    'trigger' => '<x-hw::popover id="popover"><x-hw::popover.trigger standalone>Open</x-hw::popover.trigger></x-hw::popover>',
    'content' => '<x-hw::popover id="popover"><x-hw::popover.content standalone>Content</x-hw::popover.content></x-hw::popover>',
])->throws(ViewException::class, 'requires');

it('does not treat explicit popover wiring as standalone without opt-in', function (string $template) {
    $this->blade($template);
})->with([
    'trigger' => '<x-hw::popover.trigger aria-controls="external-popover">Open</x-hw::popover.trigger>',
    'content' => '<x-hw::popover.content id="external-popover">Content</x-hw::popover.content>',
])->throws(ViewException::class, 'must be rendered inside a Popover root');

it('rejects standalone-only popover content placement props inside a popover root', function () {
    $this->blade(<<<'BLADE'
        <x-hw::popover>
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content side="top">Content</x-hw::popover.content>
        </x-hw::popover>
    BLADE);
})->throws(ViewException::class, 'Popover content side and align props are only supported when standalone is true');

it('rejects invalid standalone-only popover content placement props inside a popover root', function () {
    $this->blade(<<<'BLADE'
        <x-hw::popover>
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content side="oops">Content</x-hw::popover.content>
        </x-hw::popover>
    BLADE);
})->throws(ViewException::class, 'Popover content side and align props are only supported when standalone is true');

it('does not expose popover root props as generic component data', function () {
    $data = (new Popover)->data();
    $frameworkKeys = ['componentName', 'attributes', 'ignoredParameterNames'];
    $genericKeys = array_values(array_filter(
        array_keys($data),
        fn (string $key) => ! str_starts_with($key, 'popover') && ! in_array($key, $frameworkKeys, true),
    ));

    expect($genericKeys)->toBe([])
        ->and($data)->toHaveKeys([
            'popoverId',
            'popoverAlign',
            'popoverSide',
            'popoverSideOffset',
            'popoverAlignOffset',
            'popoverStrategy',
            'popoverFlip',
            'popoverShift',
            'popoverOpen',
            'popoverStimulus',
        ]);
});

it('does not expose popover trigger props as generic component data', function () {
    $data = (new PopoverTrigger)->data();
    $frameworkKeys = ['componentName', 'attributes', 'ignoredParameterNames'];
    $genericKeys = array_values(array_filter(
        array_keys($data),
        fn (string $key) => ! str_starts_with($key, 'popoverTrigger') && ! in_array($key, $frameworkKeys, true),
    ));

    expect($genericKeys)->toBe([])
        ->and($data)->toHaveKey('popoverTriggerStandalone');
});

it('does not expose popover content props as generic component data', function () {
    $data = (new PopoverContent)->data();
    $frameworkKeys = ['componentName', 'attributes', 'ignoredParameterNames'];
    $genericKeys = array_values(array_filter(
        array_keys($data),
        fn (string $key) => ! str_starts_with($key, 'popoverContent') && ! in_array($key, $frameworkKeys, true),
    ));

    expect($genericKeys)->toBe([])
        ->and($data)->toHaveKeys([
            'popoverContentMotion',
            'popoverContentSide',
            'popoverContentAlign',
            'popoverContentStandalone',
        ]);
});

it('does not expose popover semantic slot props as generic component data', function (object $component, string $prefix) {
    $data = $component->data();
    $frameworkKeys = ['componentName', 'attributes', 'ignoredParameterNames'];
    $genericKeys = array_values(array_filter(
        array_keys($data),
        fn (string $key) => ! str_starts_with($key, $prefix) && ! in_array($key, $frameworkKeys, true),
    ));

    expect($genericKeys)->toBe([])
        ->and($data)->toHaveKeys(["{$prefix}Tag", "{$prefix}SlotName"]);
})->with([
    [new PopoverHeader, 'popoverHeader'],
    [new PopoverTitle, 'popoverTitle'],
    [new PopoverDescription, 'popoverDescription'],
]);

it('uses semantic motion values without transition class attributes', function () {
    $on = $this->blade('
        <x-hw::popover>
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $on->assertSee('data-motion="default"', false)
        ->assertDontSee('data-transition-', false);

    $off = $this->blade('
        <x-hw::popover>
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content motion="none">Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $off->assertSee('data-motion="none"', false)
        ->assertDontSee('data-transition-', false);
});

it('merges stimulus attributes and filters popover-owned data attributes', function () {
    $view = $this->blade('
        <x-hw::popover data-controller="analytics" data-popover-side-value="top" :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'popover:opened\')">
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-controller="popover analytics"', false)
        ->assertSee('data-action="popover:opened->analytics#track"', false)
        ->assertSee('data-popover-side-value="bottom"', false)
        ->assertDontSee('data-popover-side-value="top"', false);
});

it('merges trigger and content attributes', function () {
    $view = $this->blade('
        <x-hw::popover>
            <x-hw::popover.trigger class="justify-between btn-outline">Open</x-hw::popover.trigger>
            <x-hw::popover.content class="w-80 p-6 text-sm">Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-slot="popover-trigger"', false)
        ->assertSee('justify-between', false)
        ->assertSee('btn-outline', false)
        ->assertSee('w-80 p-6 text-sm', false);
});

it('keeps popover-owned trigger and content wiring protected', function () {
    $view = $this->blade('
        <x-hw::popover id="protected-popover" side="right">
            <x-hw::popover.trigger data-action="analytics#track" data-popover-target="wrong" aria-expanded="manual">
                Open
            </x-hw::popover.trigger>

            <x-hw::popover.content id="wrong" data-state="manual" data-motion="manual" data-side="top" data-popover-target="wrong">
                Content
            </x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-action="popover#toggle analytics#track"', false)
        ->assertSee('data-popover-target="trigger"', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('id="protected-popover"', false)
        ->assertSee('data-state="closed"', false)
        ->assertSee('data-motion="default"', false)
        ->assertSee('data-side="right"', false)
        ->assertSee('data-popover-target="content"', false)
        ->assertDontSee('data-popover-target="wrong"', false)
        ->assertDontSee('aria-expanded="manual"', false)
        ->assertDontSee('id="wrong"', false)
        ->assertDontSee('data-state="manual"', false)
        ->assertDontSee('data-motion="manual"', false)
        ->assertDontSee('data-side="top"', false);
});

it('renders optional header, title and description subcomponents', function () {
    $view = $this->blade('
        <x-hw::popover>
            <x-hw::popover.trigger>Edit profile</x-hw::popover.trigger>

            <x-hw::popover.content>
                <x-hw::popover.header>
                    <x-hw::popover.title>Profile</x-hw::popover.title>
                    <x-hw::popover.description>Update public profile details.</x-hw::popover.description>
                </x-hw::popover.header>
            </x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-slot="popover-header"', false)
        ->assertSee('data-slot="popover-title"', false)
        ->assertSee('data-slot="popover-description"', false)
        ->assertSeeText('Profile')
        ->assertSeeText('Update public profile details.');
});

it('registers popover subcomponent aliases', function () {
    expect(ComponentAliases::subComponents())
        ->toHaveKey('popover.trigger')
        ->toHaveKey('popover.content')
        ->toHaveKey('popover.header')
        ->toHaveKey('popover.title')
        ->toHaveKey('popover.description');
});

it('registers popover in the catalog with its controller dependency', function () {
    $registry = HotwireRegistry::make();

    expect($registry->component('popover')->controllers)->toBe(['popover'])
        ->and($registry->controller('popover')->npm)->toHaveKey('@floating-ui/dom', '^1.8.0');
});
