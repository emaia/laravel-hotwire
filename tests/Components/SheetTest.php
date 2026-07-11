<?php

use Emaia\LaravelHotwire\Components\Sheet;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;

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

it('maps side to transform classes and size axis', function () {
    $right = $this->blade('<x-hw::sheet side="right" size="24rem"><x-hw::sheet.content /></x-hw::sheet>');
    $right->assertSee('data-sheet-dialog-hidden-class="translate-x-full"', false)
        ->assertSee('--sheet-width: 24rem', false);

    $bottom = $this->blade('<x-hw::sheet side="bottom" size="50vh"><x-hw::sheet.content /></x-hw::sheet>');
    $bottom->assertSee('data-sheet-dialog-hidden-class="translate-y-full"', false)
        ->assertSee('--sheet-height: 50vh', false);
});

it('renders frame content and loading template when frame is configured', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::sheet id="sheet-shell" frame="sheet-frame">
            <x-hw::sheet.content>Fallback</x-hw::sheet.content>
            <x-slot:loading_template>Loading sheet...</x-slot:loading_template>
        </x-hw::sheet>
    BLADE);

    $view->assertSee('<turbo-frame id="sheet-frame" data-sheet-target="dynamicContent">', false)
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
        ->assertSee('<turbo-frame id="sheet-panel" data-sheet-target="dynamicContent">', false)
        ->assertSee('<template data-sheet-target="loadingTemplate">', false);
});

it('rejects matching sheet root and frame ids', function () {
    expect(fn () => new Sheet(id: 'panel', frame: 'panel'))->toThrow(InvalidArgumentException::class);
});

it('throws on an invalid side', function () {
    expect(fn () => new Sheet(side: 'diagonal'))->toThrow(InvalidArgumentException::class);
});

it('registers sheet in the component catalog and subcomponent aliases', function () {
    $sheet = HotwireRegistry::make()->component('sheet');

    expect($sheet->key)->toBe('sheet')
        ->and($sheet->controllers)->toBe(['sheet'])
        ->and($sheet->docs)->toBe('docs/components/sheet.md');

    expect(ComponentAliases::subComponents())
        ->toHaveKey('sheet.trigger')
        ->toHaveKey('sheet.content')
        ->toHaveKey('sheet.close');
});
