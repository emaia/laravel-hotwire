<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;

// --- Sticky ---

it('renders a top sticky surface with offset css variable', function () {
    $view = $this->blade('<x-hw::sticky offset="4rem">Nav</x-hw::sticky>');

    $view->assertSee('data-slot="sticky"', false)
        ->assertSee('data-side="top"', false)
        ->assertSee('data-surface="true"', false)
        ->assertSee('style="--sticky-offset: 4rem;"', false)
        ->assertSeeText('Nav');
});

it('renders bottom sticky bars with custom tags and pass-through attributes', function () {
    $view = $this->blade('<x-hw::sticky as="footer" side="bottom" offset="12" :surface="false" data-test="actions">Actions</x-hw::sticky>');

    $html = (string) $view;

    expect($html)->toContain('<footer')
        ->and($html)->toContain('data-slot="sticky"')
        ->and($html)->toContain('data-side="bottom"')
        ->and($html)->toContain('data-surface="false"')
        ->and($html)->toContain('style="--sticky-offset: 12;"')
        ->and($html)->toContain('data-test="actions"')
        ->and($html)->toContain('</footer>');
});

it('normalizes invalid sticky sides to top', function () {
    $view = $this->blade('<x-hw::sticky side="left">Nav</x-hw::sticky>');

    $view->assertSee('data-side="top"', false)
        ->assertDontSee('data-side="left"', false);
});

// --- Navbar ---

it('renders an accessible navbar with item links', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::navbar aria-label="Sections">
            <x-hw::navbar.item href="/basic">Basic</x-hw::navbar.item>
            <x-hw::navbar.item href="/content" current>Content</x-hw::navbar.item>
        </x-hw::navbar>
    BLADE);

    $html = (string) $view;

    expect($html)->toContain('<nav')
        ->and($html)->toContain('data-slot="navbar"')
        ->and($html)->toContain('aria-label="Sections"')
        ->and($html)->toContain('data-orientation="horizontal"')
        ->and($html)->toContain('data-variant="line"')
        ->and($html)->toContain('data-overflow="scroll"')
        ->and($html)->toContain('href="/basic"')
        ->and($html)->toContain('href="/content"')
        ->and($html)->toContain('data-current="true"')
        ->and($html)->toContain('aria-current="page"');
});

it('renders vertical navbar state and passes attributes through', function () {
    $view = $this->blade('<x-hw::navbar orientation="vertical" variant="pills" overflow="visible" data-test="nav">Nav</x-hw::navbar>');

    $view->assertSee('data-orientation="vertical"', false)
        ->assertSee('data-variant="pills"', false)
        ->assertSee('data-overflow="visible"', false)
        ->assertSee('data-test="nav"', false);
});

it('wraps navbar in sticky surface when sticky sugar is enabled', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::navbar sticky sticky-side="bottom" sticky-offset="2rem" aria-label="Sections">
            <x-hw::navbar.item href="/content">Content</x-hw::navbar.item>
        </x-hw::navbar>
    BLADE);

    $html = (string) $view;

    expect($html)->toContain('data-slot="sticky"')
        ->and($html)->toContain('data-side="bottom"')
        ->and($html)->toContain('style="--sticky-offset: 2rem;"')
        ->and($html)->toContain('<nav')
        ->and($html)->toContain('data-slot="navbar"')
        ->and($html)->not->toContain('sticky-side=')
        ->and($html)->not->toContain('sticky-offset=');
});

it('renders navbar items as buttons when no href is provided', function () {
    $view = $this->blade('<x-hw::navbar><x-hw::navbar.item current>Overview</x-hw::navbar.item></x-hw::navbar>');

    $html = (string) $view;

    expect($html)->toContain('<button')
        ->and($html)->toContain('type="button"')
        ->and($html)->toContain('data-current="true"')
        ->and($html)->not->toContain('aria-current="page"');
});

it('supports explicit navbar item tags', function () {
    $view = $this->blade('<x-hw::navbar><x-hw::navbar.item as="span">Label</x-hw::navbar.item></x-hw::navbar>');

    expect((string) $view)->toContain('<span')
        ->and((string) $view)->toContain('data-slot="navbar-item"')
        ->and((string) $view)->toContain('</span>');
});

it('renders disabled navbar buttons and disabled navbar links safely', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::navbar>
            <x-hw::navbar.item disabled>Button</x-hw::navbar.item>
            <x-hw::navbar.item href="/billing" disabled>Billing</x-hw::navbar.item>
        </x-hw::navbar>
    BLADE);

    $html = (string) $view;

    expect($html)->toContain('<button')
        ->and($html)->toContain('disabled')
        ->and($html)->toContain('<a')
        ->and($html)->toContain('aria-disabled="true"')
        ->and($html)->toContain('tabindex="-1"')
        ->and($html)->not->toContain('href="/billing"');
});

it('passes attributes through to navbar items', function () {
    $view = $this->blade('<x-hw::navbar><x-hw::navbar.item href="/docs" class="font-bold" data-test="docs">Docs</x-hw::navbar.item></x-hw::navbar>');

    $view->assertSee('class="font-bold"', false)
        ->assertSee('data-test="docs"', false);
});

// --- Registry ---

it('registers sticky and navbar components in the catalog', function () {
    $components = HotwireRegistry::make()->components();

    expect($components)->toHaveKeys(['sticky', 'navbar', 'navbar.item'])
        ->and($components['sticky']->docs)->toBe('docs/components/sticky.md')
        ->and($components['navbar']->docs)->toBe('docs/components/navbar.md')
        ->and($components['navbar.item']->docs)->toBe('docs/components/navbar.md');
});

it('registers the navbar item alias', function () {
    expect(ComponentAliases::subComponents())->toHaveKey('navbar.item');
});
