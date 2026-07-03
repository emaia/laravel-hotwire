<?php

it('renders a card root with semantic size state', function () {
    $view = $this->blade('<x-hw::card size="sm">Content</x-hw::card>');

    $view->assertSee('data-slot="card"', false)
        ->assertSee('data-size="sm"', false)
        ->assertSeeText('Content')
        ->assertDontSee('rounded-xl', false);
});

it('renders card subcomponents with semantic slots', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::card>
            <x-hw::card.header>
                <x-hw::card.title>Revenue</x-hw::card.title>
                <x-hw::card.description>Last 30 days</x-hw::card.description>
                <x-hw::card.action><x-hw::button size="sm">Export</x-hw::button></x-hw::card.action>
            </x-hw::card.header>
            <x-hw::card.content>$12,400</x-hw::card.content>
            <x-hw::card.footer>Updated now</x-hw::card.footer>
        </x-hw::card>
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
    $view = $this->blade('<x-hw::card id="metrics" class="max-w-md" data-test="card">Metrics</x-hw::card>');

    $view->assertSee('id="metrics"', false)
        ->assertSee('class="max-w-md"', false)
        ->assertSee('data-test="card"', false);
});
