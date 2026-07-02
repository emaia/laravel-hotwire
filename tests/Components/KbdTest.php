<?php

it('renders a kbd element with semantic slot', function () {
    $view = $this->blade('<x-hwc::kbd>⌘K</x-hwc::kbd>');

    $view->assertSee('<kbd', false)
        ->assertSee('data-slot="kbd"', false)
        ->assertSee('</kbd>', false)
        ->assertSeeText('⌘K')
        ->assertDontSee('inline-flex', false);
});

it('renders a kbd group slot', function () {
    $view = $this->blade('<x-hwc::kbd.group><x-hwc::kbd>⌘</x-hwc::kbd><x-hwc::kbd>K</x-hwc::kbd></x-hwc::kbd.group>');

    $view->assertSee('data-slot="kbd-group"', false)
        ->assertSeeText('⌘')
        ->assertSeeText('K');
});

it('passes through attributes', function () {
    $view = $this->blade('<x-hwc::kbd id="shortcut" class="tracking-wide" aria-label="Command K">⌘K</x-hwc::kbd>');

    $view->assertSee('id="shortcut"', false)
        ->assertSee('class="tracking-wide"', false)
        ->assertSee('aria-label="Command K"', false);
});
