<?php

it('renders a horizontal button group by default', function () {
    $view = $this->blade('<x-hwc::button-group><x-hwc::button>One</x-hwc::button><x-hwc::button>Two</x-hwc::button></x-hwc::button-group>');

    $view->assertSee('role="group"', false)
        ->assertSee('data-slot="button-group"', false)
        ->assertSee('data-orientation="horizontal"', false)
        ->assertSeeText('One')
        ->assertDontSee('items-stretch', false);
});

it('renders a vertical button group state', function () {
    $view = $this->blade('<x-hwc::button-group orientation="vertical"><x-hwc::button>One</x-hwc::button></x-hwc::button-group>');

    $view->assertSee('data-orientation="vertical"', false);
});

it('renders text and separator subcomponents', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hwc::button-group>
            <x-hwc::button-group.text>Sort</x-hwc::button-group.text>
            <x-hwc::button-group.separator />
            <x-hwc::button>Newest</x-hwc::button>
        </x-hwc::button-group>
    BLADE);

    $view->assertSee('data-slot="button-group-text"', false)
        ->assertSee('data-slot="button-group-separator"', false)
        ->assertSee('data-orientation="vertical"', false)
        ->assertSeeText('Sort')
        ->assertSeeText('Newest');
});

it('passes through attributes', function () {
    $view = $this->blade('<x-hwc::button-group id="actions" class="mt-4" aria-label="Actions"><x-hwc::button>Save</x-hwc::button></x-hwc::button-group>');

    $view->assertSee('id="actions"', false)
        ->assertSee('class="mt-4"', false)
        ->assertSee('aria-label="Actions"', false);
});
