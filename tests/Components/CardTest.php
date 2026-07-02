<?php

it('renders a card root with semantic size state', function () {
    $view = $this->blade('<x-hwc::card size="sm">Content</x-hwc::card>');

    $view->assertSee('data-slot="card"', false)
        ->assertSee('data-size="sm"', false)
        ->assertSeeText('Content')
        ->assertDontSee('rounded-xl', false);
});

it('renders card subcomponents with semantic slots', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hwc::card>
            <x-hwc::card.header>
                <x-hwc::card.title>Revenue</x-hwc::card.title>
                <x-hwc::card.description>Last 30 days</x-hwc::card.description>
                <x-hwc::card.action><x-hwc::button size="sm">Export</x-hwc::button></x-hwc::card.action>
            </x-hwc::card.header>
            <x-hwc::card.content>$12,400</x-hwc::card.content>
            <x-hwc::card.footer>Updated now</x-hwc::card.footer>
        </x-hwc::card>
    BLADE);

    $view->assertSee('data-slot="card-header"', false)
        ->assertSee('data-slot="card-title"', false)
        ->assertSee('data-slot="card-description"', false)
        ->assertSee('data-slot="card-action"', false)
        ->assertSee('data-slot="card-content"', false)
        ->assertSee('data-slot="card-footer"', false)
        ->assertSeeText('Revenue')
        ->assertSeeText('Export')
        ->assertSeeText('Updated now');
});

it('passes through attributes', function () {
    $view = $this->blade('<x-hwc::card id="metrics" class="max-w-md" data-test="card">Metrics</x-hwc::card>');

    $view->assertSee('id="metrics"', false)
        ->assertSee('class="max-w-md"', false)
        ->assertSee('data-test="card"', false);
});
