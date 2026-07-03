<?php

it('renders an item with semantic variant and size state', function () {
    $view = $this->blade('<x-hw::item variant="outline" size="sm">Profile</x-hw::item>');

    $view->assertSee('data-slot="item"', false)
        ->assertSee('data-variant="outline"', false)
        ->assertSee('data-size="sm"', false)
        ->assertSeeText('Profile')
        ->assertDontSee('rounded-lg', false);
});

it('renders as a link via the as prop', function () {
    $view = $this->blade('<x-hw::item as="a" href="/profile">Profile</x-hw::item>');

    $view->assertSee('<a', false)
        ->assertSee('href="/profile"', false)
        ->assertSee('data-slot="item"', false)
        ->assertSee('</a>', false)
        ->assertDontSee('<div data-slot="item"', false);
});

it('renders item subcomponents with semantic slots', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::item.group>
            <x-hw::item variant="muted" size="xs">
                <x-hw::item.header>
                    <x-hw::item.title>Deploy</x-hw::item.title>
                    <x-hw::badge>Live</x-hw::badge>
                </x-hw::item.header>
                <x-hw::item.media variant="icon"><x-hw::icon name="check" /></x-hw::item.media>
                <x-hw::item.content>
                    <x-hw::item.description>Production deploy finished.</x-hw::item.description>
                </x-hw::item.content>
                <x-hw::item.actions><x-hw::button size="sm">Open</x-hw::button></x-hw::item.actions>
                <x-hw::item.footer>Just now</x-hw::item.footer>
            </x-hw::item>
            <x-hw::item.separator />
        </x-hw::item.group>
    BLADE);

    $view->assertSee('role="list"', false)
        ->assertSee('data-slot="item-group"', false)
        ->assertSee('data-slot="item-header"', false)
        ->assertSee('data-slot="item-title"', false)
        ->assertSee('data-slot="item-media"', false)
        ->assertSee('data-variant="icon"', false)
        ->assertSee('data-slot="item-content"', false)
        ->assertSee('data-slot="item-description"', false)
        ->assertSee('data-slot="item-actions"', false)
        ->assertSee('data-slot="item-footer"', false)
        ->assertSee('data-slot="item-separator"', false)
        ->assertSeeText('Deploy')
        ->assertSeeText('Just now');
});

it('passes through attributes', function () {
    $view = $this->blade('<x-hw::item id="notification" class="gap-4" data-test="notification">Item</x-hw::item>');

    $view->assertSee('id="notification"', false)
        ->assertSee('class="gap-4"', false)
        ->assertSee('data-test="notification"', false);
});
