<?php

it('renders polymorphic item buttons with a safe type', function () {
    $view = $this->blade('<x-hw::item as="button">Choose</x-hw::item>');

    $view->assertSee('<button', false)
        ->assertSee('type="button"', false);
});

it('preserves accessibility attributes on enabled items', function () {
    $view = $this->blade('<x-hw::item tabindex="0" aria-disabled="false">Choose</x-hw::item>');

    $view->assertSee('tabindex="0"', false)
        ->assertSee('aria-disabled="false"', false);
});

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

it('treats a null disabled binding on item links as enabled', function () {
    $view = $this->blade('<x-hw::item as="a" href="/profile" :disabled="$disabled">Profile</x-hw::item>', ['disabled' => null]);

    $view->assertSee('href="/profile"', false)
        ->assertDontSee('aria-disabled="true"', false);
});

it('renders item subcomponents with semantic slots', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::item.group>
            <x-hw::item variant="muted" size="xs">
                <x-hw::item.header>
                    <x-hw::item.title>Deploy</x-hw::item.title>
                    <span>Live</span>
                </x-hw::item.header>
                <x-hw::item.media variant="icon"><svg aria-hidden="true"></svg></x-hw::item.media>
                <x-hw::item.content>
                    <x-hw::item.description>Production deploy finished.</x-hw::item.description>
                </x-hw::item.content>
                <x-hw::item.actions>Open</x-hw::item.actions>
                <x-hw::item.footer>Just now</x-hw::item.footer>
            </x-hw::item>
            <x-hw::item.separator />
        </x-hw::item.group>
    BLADE);

    preg_match_all('/data-slot="([a-z][a-z0-9-]*)"/', $html, $matches);

    expect($matches[1])->toBe([
        'item-group',
        'item',
        'item-header',
        'item-title',
        'item-media',
        'item-content',
        'item-description',
        'item-actions',
        'item-footer',
        'item-separator',
    ])->and($html)
        ->toContain('role="list"')
        ->toContain('data-variant="icon"')
        ->toContain('Deploy')
        ->toContain('Just now');
});

it('preserves separator orientation semantics', function () {
    $view = $this->blade('<x-hw::item.separator orientation="vertical" />');

    $view->assertSee('data-slot="item-separator"', false)
        ->assertSee('data-orientation="vertical"', false)
        ->assertSee('role="separator"', false)
        ->assertSee('aria-orientation="vertical"', false);
});

it('passes through attributes', function () {
    $view = $this->blade('<x-hw::item id="notification" class="gap-4" data-test="notification">Item</x-hw::item>');

    $view->assertSee('id="notification"', false)
        ->assertSee('class="gap-4"', false)
        ->assertSee('data-test="notification"', false);
});
