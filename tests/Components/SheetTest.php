<?php

use Emaia\LaravelHotwire\Components\Sheet;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Emaia\LaravelHotwire\Support\FieldContext;
use Emaia\LaravelHotwire\Support\OverlayLabelContext;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

it('renders sheet markup and controller hooks', function () {
    $view = $this->blade('<x-hw::sheet><x-hw::sheet.content>Body</x-hw::sheet.content></x-hw::sheet>');

    $view->assertSee('data-slot="sheet"', false)
        ->assertSee('data-controller="sheet"', false)
        ->assertSee('data-slot="sheet-overlay"', false)
        ->assertSee('data-sheet-target="modal"', false)
        ->assertSee('data-slot="sheet-content"', false)
        ->assertSee('data-sheet-target="dialog"', false)
        ->assertSee('data-slot="sheet-close-icon"', false)
        ->assertSee('aria-label="Close sheet"', false)
        ->assertSee('role="dialog"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee('data-state="closed"', false)
        ->assertSee('data-motion="default"', false)
        ->assertSee('inert', false)
        ->assertDontSee('data-sheet-hidden-class', false)
        ->assertDontSee('data-sheet-open-duration-value', false)
        ->assertSee('--sheet-width: 75%', false)
        ->assertSeeText('Body');
});

it('renders trigger close and semantic subcomponents', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::sheet side="right">
            <x-hw::sheet.trigger>Open</x-hw::sheet.trigger>
            <x-hw::sheet.content>
                <x-hw::sheet.header>
                    <x-hw::sheet.title>Title</x-hw::sheet.title>
                    <x-hw::sheet.description>Description</x-hw::sheet.description>
                </x-hw::sheet.header>
                <x-hw::sheet.footer>
                    <x-hw::sheet.close>Close</x-hw::sheet.close>
                </x-hw::sheet.footer>
            </x-hw::sheet.content>
        </x-hw::sheet>
    BLADE);

    $view->assertSee('data-slot="sheet-trigger"', false)
        ->assertSee('data-action="click-&gt;sheet#toggle"', false)
        ->assertSee('data-side="right"', false)
        ->assertSee('data-slot="sheet-header"', false)
        ->assertSee('data-slot="sheet-title"', false)
        ->assertSee('data-slot="sheet-description"', false)
        ->assertSee('data-slot="sheet-footer"', false)
        ->assertSee('data-slot="sheet-close"', false)
        ->assertSee('data-action="sheet#close"', false);
});

it('maps side to semantic state and size axis', function () {
    $right = $this->blade('<x-hw::sheet side="right" size="24rem"><x-hw::sheet.content /></x-hw::sheet>');
    $right->assertSee('data-side="right"', false)
        ->assertSee('--sheet-width: 24rem', false);

    $bottom = $this->blade('<x-hw::sheet side="bottom" size="50vh"><x-hw::sheet.content /></x-hw::sheet>');
    $bottom->assertSee('data-side="bottom"', false)
        ->assertSee('--sheet-height: 50vh', false);
});

it('normalizes sheet motion', function () {
    $none = $this->blade('<x-hw::sheet motion="none"><x-hw::sheet.content /></x-hw::sheet>');
    $invalid = $this->blade('<x-hw::sheet motion="spin"><x-hw::sheet.content /></x-hw::sheet>');

    $none->assertSee('data-motion="none"', false);
    $invalid->assertSee('data-motion="default"', false);
});

it('renders frame content and loading template when frame is configured', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::sheet id="sheet-shell" frame="sheet-frame">
            <x-hw::sheet.content>Fallback</x-hw::sheet.content>
            <x-slot:loading_template>Loading sheet...</x-slot:loading_template>
        </x-hw::sheet>
    BLADE);

    $view->assertSee('<turbo-frame id="sheet-frame" data-sheet-target="dynamicContent"', false)
        ->assertSee('Fallback')
        ->assertSee('<template data-sheet-target="loadingTemplate">', false)
        ->assertSee('Loading sheet...');
});

it('renders a complete frame host when sheet content is omitted', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::sheet frame="sheet-panel" side="right">
            <x-slot:loading_template>
                <div class="p-6">Loading...</div>
            </x-slot:loading_template>
        </x-hw::sheet>
    BLADE);

    $view->assertSee('data-sheet-target="modal"', false)
        ->assertSee('data-sheet-target="dialog"', false)
        ->assertSee('<turbo-frame id="sheet-panel" data-sheet-target="dynamicContent"', false)
        ->assertSee('<template data-sheet-target="loadingTemplate">', false);
});

it('mounts view transitions on automatic and explicit sheet frame hosts', function (string $template) {
    $view = $this->blade($template);
    $html = (string) $view;
    $xpath = new DOMXPath(dom($html));
    $root = $xpath->query('//*[@data-slot="sheet"]')->item(0);
    $frame = $xpath->query('//turbo-frame[@id="sheet-panel"]')->item(0);

    expect($root->getAttribute('data-controller'))->toBe('sheet custom')
        ->and($root->getAttribute('data-test-id'))->toBe('sheet-root')
        ->and($root->hasAttribute('view-transition'))->toBeFalse()
        ->and($frame->getAttribute('data-controller'))->toBe('turbo--view-transition')
        ->and($frame->getAttribute('data-turbo--view-transition-skip-initial-value'))->toBe('true')
        ->and($frame->getAttribute('data-sheet-target'))->toBe('dynamicContent')
        ->and($frame->getAttribute('data-sheet-frame-owner'))->toBe('sheet-shell')
        ->and(substr_count($html, 'data-controller="turbo--view-transition"'))->toBe(1);
})->with([
    'automatic host' => '<x-hw::sheet id="sheet-shell" frame="sheet-panel" view-transition data-controller="custom" data-test-id="sheet-root" />',
    'explicit host' => '<x-hw::sheet id="sheet-shell" frame="sheet-panel" view-transition data-controller="custom" data-test-id="sheet-root"><x-hw::sheet.content>Fallback</x-hw::sheet.content></x-hw::sheet>',
]);

it('does not mount sheet view transitions by default or without a frame', function (string $template) {
    $view = $this->blade($template);
    $xpath = new DOMXPath(dom((string) $view));
    $root = $xpath->query('//*[@data-slot="sheet"]')->item(0);

    expect((string) $view)->not->toContain('turbo--view-transition')
        ->not->toContain('data-turbo--view-transition-skip-initial-value')
        ->and($root->hasAttribute('view-transition'))->toBeFalse();
})->with([
    'disabled' => '<x-hw::sheet frame="sheet-panel" />',
    'no frame' => '<x-hw::sheet view-transition><x-hw::sheet.content>Content</x-hw::sheet.content></x-hw::sheet>',
]);

it('adds one frame host alongside trigger-only sheet content', function () {
    $view = $this->blade('<x-hw::sheet frame="sheet-panel"><x-hw::sheet.trigger>Open</x-hw::sheet.trigger></x-hw::sheet>');

    expect(substr_count((string) $view, '<turbo-frame'))->toBe(1);
});

it('rejects more than one sheet frame host', function () {
    $this->blade('<x-hw::sheet frame="sheet-panel"><x-hw::sheet.content>One</x-hw::sheet.content><x-hw::sheet.content>Two</x-hw::sheet.content></x-hw::sheet>');
})->throws(ViewException::class, 'A sheet with a frame prop must render exactly one sheet.content host.');

it('does not mistake arbitrary sheet owner metadata for a frame host', function () {
    $view = $this->blade('<x-hw::sheet id="sheet-shell" frame="sheet-panel"><div data-sheet-frame-owner="sheet-shell">Content</div></x-hw::sheet>');

    expect(substr_count((string) $view, '<turbo-frame'))->toBe(1);
});

it('keeps sheet aware context through an intermediate component', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $view = $this->blade(<<<'BLADE'
        <x-hw::sheet id="sheet-shell" side="left" frame="sheet-panel" :backdrop="false" motion="none" view-transition>
            <x-overlay-context-wrapper
                id="shadow-overlay"
                side="bottom"
                :backdrop="true"
                frame="shadow-frame"
                motion="default"
                :view-transition="false"
            >
                <x-hw::sheet.trigger>Open</x-hw::sheet.trigger>
                <x-hw::sheet.content>Owned content</x-hw::sheet.content>
            </x-overlay-context-wrapper>
        </x-hw::sheet>
    BLADE);

    $html = (string) $view;

    expect($html)->toContain('data-side="left"')
        ->toContain('data-motion="none"')
        ->toContain('id="sheet-panel"')
        ->toContain('data-sheet-frame-owner="sheet-shell"')
        ->toContain('turbo--view-transition')
        ->not->toContain('data-side="bottom"')
        ->not->toContain('id="shadow-frame"')
        ->not->toContain('data-sheet-frame-owner="shadow-overlay"')
        ->not->toContain('data-slot="sheet-backdrop"')
        ->and(substr_count($html, 'data-slot="sheet-overlay"'))->toBe(1)
        ->and(substr_count($html, '<turbo-frame'))->toBe(1);
});

it('requires sheet content to render inside a sheet root', function () {
    $this->blade('<x-hw::sheet.content>Content</x-hw::sheet.content>');
})
    ->throws(ViewException::class, 'must be rendered inside a Sheet root');

it('renders sheet triggers without requiring an owning sheet root', function () {
    $view = $this->blade('<x-hw::sheet.trigger>Open</x-hw::sheet.trigger>');

    $view->assertSee('data-slot="sheet-trigger"', false)
        ->assertSee('data-action="click-&gt;sheet#toggle"', false);
});

it('rejects an unmanaged turbo frame with the sheet frame id', function () {
    $this->blade('<x-hw::sheet id="sheet-shell" frame="sheet-panel"><turbo-frame id="sheet-panel"></turbo-frame></x-hw::sheet>');
})->throws(ViewException::class, 'A sheet with a frame prop must render exactly one sheet.content host.');

it('keeps nested sheet frame hosts scoped to their owner', function () {
    $view = $this->blade('<x-hw::sheet id="outer" frame="outer-frame"><x-hw::sheet id="inner" frame="inner-frame"></x-hw::sheet></x-hw::sheet>');

    expect(substr_count((string) $view, '<turbo-frame'))->toBe(2);
});

it('rejects matching sheet root and frame ids', function () {
    expect(fn () => new Sheet(id: 'panel', frame: 'panel'))->toThrow(InvalidArgumentException::class);
});

it('throws on an invalid side', function () {
    expect(fn () => new Sheet(side: 'diagonal'))->toThrow(InvalidArgumentException::class);
});

it('keeps view transition as the final positional constructor argument', function () {
    $component = new Sheet('', 'right', '', null, true, 'none', true, true, true, null, true);

    expect($component->motion)->toBe('none')
        ->and($component->viewTransition)->toBeTrue();
});

it('does not expose sheet root props as generic component data', function () {
    $data = (new Sheet)->data();
    $frameworkKeys = ['componentName', 'attributes', 'ignoredParameterNames'];
    $genericKeys = array_values(array_filter(
        array_keys($data),
        fn (string $key) => ! str_starts_with($key, 'sheet') && ! in_array($key, [...$frameworkKeys, 'compute', ...array_keys(FieldContext::boundaryData()), ...array_keys(OverlayLabelContext::boundaryData())], true),
    ));

    expect($genericKeys)->toBe([])
        ->and($data)->toHaveKeys([
            'sheetId',
            'sheetSide',
            'sheetBackdrop',
            'sheetFrame',
            'sheetMotion',
            'sheetViewTransition',
        ])
        ->and($data)->toHaveKey('fieldContext', null)
        ->toHaveKey('fieldControlContext', null);
});

it('registers sheet in the component catalog and subcomponent aliases', function () {
    $sheet = HotwireRegistry::make()->component('sheet');

    expect($sheet->key)->toBe('sheet')
        ->and($sheet->controllers)->toBe(['sheet', 'turbo--view-transition'])
        ->and($sheet->docs)->toBe('docs/components/sheet.md');

    expect(ComponentAliases::subComponents())
        ->toHaveKey('sheet.trigger')
        ->toHaveKey('sheet.content')
        ->toHaveKey('sheet.close');
});
