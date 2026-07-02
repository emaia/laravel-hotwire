<?php

it('renders an item with semantic variant and size state', function () {
    $view = $this->blade('<x-hwc::item variant="outline" size="sm">Profile</x-hwc::item>');

    $view->assertSee('data-slot="item"', false)
        ->assertSee('data-variant="outline"', false)
        ->assertSee('data-size="sm"', false)
        ->assertSeeText('Profile')
        ->assertDontSee('rounded-lg', false);
});

it('renders as a link via the as prop', function () {
    $view = $this->blade('<x-hwc::item as="a" href="/profile">Profile</x-hwc::item>');

    $view->assertSee('<a', false)
        ->assertSee('href="/profile"', false)
        ->assertSee('data-slot="item"', false)
        ->assertSee('</a>', false)
        ->assertDontSee('<div data-slot="item"', false);
});

it('renders item subcomponents with semantic slots', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hwc::item.group>
            <x-hwc::item variant="muted" size="xs">
                <x-hwc::item.header>
                    <x-hwc::item.title>Deploy</x-hwc::item.title>
                    <x-hwc::badge>Live</x-hwc::badge>
                </x-hwc::item.header>
                <x-hwc::item.media variant="icon"><x-hwc::icon name="check" /></x-hwc::item.media>
                <x-hwc::item.content>
                    <x-hwc::item.description>Production deploy finished.</x-hwc::item.description>
                </x-hwc::item.content>
                <x-hwc::item.actions><x-hwc::button size="sm">Open</x-hwc::button></x-hwc::item.actions>
                <x-hwc::item.footer>Just now</x-hwc::item.footer>
            </x-hwc::item>
            <x-hwc::item.separator />
        </x-hwc::item.group>
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
    $view = $this->blade('<x-hwc::item id="notification" class="gap-4" data-test="notification">Item</x-hwc::item>');

    $view->assertSee('id="notification"', false)
        ->assertSee('class="gap-4"', false)
        ->assertSee('data-test="notification"', false);
});
