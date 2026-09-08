<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;

// --- Rendering ---

it('renders Input Group family anatomy with semantic slots', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::input-group>
            <input data-slot="input-group-control" name="amount" />
            <x-hw::input-group.addon align="inline-end">
                USD
            </x-hw::input-group.addon>
        </x-hw::input-group>
    BLADE);

    preg_match_all('/data-slot="([a-z][a-z0-9-]*)"/', $html, $matches);

    expect($matches[1])->toBe([
        'input-group',
        'input-group-control',
        'input-group-addon',
    ])->and($html)
        ->toContain('data-align="inline-end"')
        ->toContain('USD');
});

it('keeps addons after the input in DOM order', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::input-group>
            <x-hw::input name="q" />
            <x-hw::input-group.addon align="inline-start">Search</x-hw::input-group.addon>
        </x-hw::input-group>
    BLADE);

    expect($html)->toMatch('/<input[^>]*data-slot="input"[\s\S]*data-slot="input-group-addon"/');
});

it('composes with clearable input wrappers', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::input-group>
            <x-hw::input name="search" clearable />
            <x-hw::input-group.addon align="inline-start">Search</x-hw::input-group.addon>
        </x-hw::input-group>
    BLADE);

    $view->assertSee('data-slot="input-wrapper"', false);
    $view->assertSee('data-clearable="true"', false);
    $view->assertSee('data-slot="clear-input-button"', false);
});

it('composes with textarea and buttons', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::input-group>
            <x-hw::textarea name="message" />
            <x-hw::input-group.addon align="block-end">
                <x-hw::button type="submit" variant="ghost" size="sm">Send</x-hw::button>
            </x-hw::input-group.addon>
        </x-hw::input-group>
    BLADE);

    $view->assertSee('data-slot="textarea"', false);
    $view->assertSee('data-align="block-end"', false);
    $view->assertSee('data-slot="button"', false);
});

// --- Defaults and attributes ---

it('defaults addon alignment to inline-start', function () {
    $view = $this->blade('<x-hw::input-group.addon>https://</x-hw::input-group.addon>');

    $view->assertSee('data-align="inline-start"', false);
});

it('passes attributes through to the group and addon', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::input-group class="w-full" data-test="group">
            <x-hw::input name="url" />
            <x-hw::input-group.addon class="font-mono" data-test="addon">https://</x-hw::input-group.addon>
        </x-hw::input-group>
    BLADE);

    $view->assertSee('class="w-full"', false);
    $view->assertSee('data-test="group"', false);
    $view->assertSee('class="font-mono"', false);
    $view->assertSee('data-test="addon"', false);
});

// --- Registry ---

it('registers input group in the component catalog', function () {
    $entry = HotwireRegistry::make()->component('input-group');

    expect($entry)->not->toBeNull()
        ->and($entry->docs)->toBe('docs/components/input-group.md')
        ->and($entry->controllers)->toBe([]);
});
