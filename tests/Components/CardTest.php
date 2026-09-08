<?php

it('renders a card root with semantic size state', function () {
    $view = $this->blade('<x-hw::card size="sm">Content</x-hw::card>');

    $view->assertSee('data-slot="card"', false)
        ->assertSee('data-size="sm"', false)
        ->assertSeeText('Content')
        ->assertDontSee('rounded-xl', false);
});

it('renders card subcomponents with semantic slots', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::card>
            <x-hw::card.header>
                <x-hw::card.title>Revenue</x-hw::card.title>
                <x-hw::card.description>Last 30 days</x-hw::card.description>
                <x-hw::card.action>Export</x-hw::card.action>
            </x-hw::card.header>
            <x-hw::card.content>$12,400</x-hw::card.content>
            <x-hw::card.footer>Updated now</x-hw::card.footer>
        </x-hw::card>
    BLADE);

    preg_match_all('/data-slot="([a-z][a-z0-9-]*)"/', $html, $matches);

    expect(array_values(array_unique($matches[1])))->toBe([
        'card',
        'card-header',
        'card-title',
        'card-description',
        'card-action',
        'card-content',
        'card-footer',
    ])->and($html)->toContain('Revenue')->toContain('Export')->toContain('Updated now');
});

it('passes through attributes', function () {
    $view = $this->blade('<x-hw::card id="metrics" class="max-w-md" data-test="card">Metrics</x-hw::card>');

    $view->assertSee('id="metrics"', false)
        ->assertSee('class="max-w-md"', false)
        ->assertSee('data-test="card"', false);
});
