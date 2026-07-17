<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;

// --- Rendering ---

it('renders an input group around existing input components', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::input-group>
            <x-hw::input name="search" placeholder="Search" />
            <x-hw::input-group.addon align="inline-start">
                <svg data-testid="search-icon"></svg>
            </x-hw::input-group.addon>
        </x-hw::input-group>
    BLADE);

    $view->assertSee('data-slot="input-group"', false);
    $view->assertSee('data-slot="input"', false);
    $view->assertSee('data-slot="input-group-addon"', false);
    $view->assertSee('data-align="inline-start"', false);
    $view->assertSee('data-testid="search-icon"', false);
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

it('supports custom controls using input-group-control slot', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::input-group>
            <input data-slot="input-group-control" name="custom" />
            <x-hw::input-group.addon>USD</x-hw::input-group.addon>
        </x-hw::input-group>
    BLADE);

    $view->assertSee('data-slot="input-group-control"', false);
    $view->assertSee('USD', false);
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
