<?php

it('renders a horizontal button group by default', function () {
    $view = $this->blade('<x-hw::button-group><x-hw::button>One</x-hw::button><x-hw::button>Two</x-hw::button></x-hw::button-group>');

    $view->assertSee('role="group"', false)
        ->assertSee('data-slot="button-group"', false)
        ->assertSee('data-orientation="horizontal"', false)
        ->assertSeeText('One')
        ->assertDontSee('items-stretch', false);
});

it('renders a vertical button group state', function () {
    $view = $this->blade('<x-hw::button-group orientation="vertical"><x-hw::button>One</x-hw::button></x-hw::button-group>');

    $view->assertSee('data-orientation="vertical"', false);
});

it('renders text and separator subcomponents', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::button-group>
            <x-hw::button-group.text>Sort</x-hw::button-group.text>
            <x-hw::button-group.separator />
            <x-hw::button>Newest</x-hw::button>
        </x-hw::button-group>
    BLADE);

    $view->assertSee('data-slot="button-group-text"', false)
        ->assertSee('data-slot="button-group-separator"', false)
        ->assertSee('data-orientation="vertical"', false)
        ->assertSeeText('Sort')
        ->assertSeeText('Newest');
});

it('passes through attributes', function () {
    $view = $this->blade('<x-hw::button-group id="actions" class="mt-4" aria-label="Actions"><x-hw::button>Save</x-hw::button></x-hw::button-group>');

    $view->assertSee('id="actions"', false)
        ->assertSee('class="mt-4"', false)
        ->assertSee('aria-label="Actions"', false);
});
