<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;

it('renders hover card controller, trigger and content wiring', function () {
    $view = $this->blade('
        <x-hw::hover-card>
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content><p>Preview content</p></x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('data-slot="hover-card"', false)
        ->assertSee('data-controller="hover-card"', false)
        ->assertSee('<button type="button"', false)
        ->assertSee('data-slot="hover-card-trigger"', false)
        ->assertSee('data-variant="link"', false)
        ->assertSee('data-size="default"', false)
        ->assertSee('data-hover-card-target="trigger"', false)
        ->assertSee('mouseenter->hover-card#pointerEnter', false)
        ->assertSee('focusin->hover-card#focusIn', false)
        ->assertSee('aria-describedby="hover-card-', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertDontSee('tabindex="0"', false)
        ->assertSee('data-slot="hover-card-content"', false)
        ->assertSee('data-hover-card-target="content"', false)
        ->assertSee('role="tooltip"', false)
        ->assertSee('User')
        ->assertSee('Preview content');
});

it('links the trigger and content via id and aria-describedby', function () {
    $view = $this->blade('
        <x-hw::hover-card id="user-preview">
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('id="user-preview"', false)
        ->assertSee('aria-describedby="user-preview"', false);
});

it('emits delay and positioning defaults for Floating UI', function () {
    $view = $this->blade('
        <x-hw::hover-card>
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('data-hover-card-open-delay-value="10"', false)
        ->assertSee('data-hover-card-close-delay-value="100"', false)
        ->assertSee('data-hover-card-side-value="bottom"', false)
        ->assertSee('data-hover-card-align-value="start"', false)
        ->assertSee('data-hover-card-side-offset-value="4"', false)
        ->assertSee('data-hover-card-align-offset-value="0"', false)
        ->assertSee('data-hover-card-strategy-value="fixed"', false)
        ->assertSee('data-hover-card-flip-value="true"', false)
        ->assertSee('data-hover-card-shift-value="true"', false)
        ->assertSee('data-side="bottom"', false)
        ->assertSee('data-align="start"', false);
});

it('emits custom delay and positioning values', function () {
    $view = $this->blade('
        <x-hw::hover-card :open-delay="50" :close-delay="25" side="right" align="end" :side-offset="12" :align-offset="-4" strategy="absolute" :flip="false" :shift="false">
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('data-hover-card-open-delay-value="50"', false)
        ->assertSee('data-hover-card-close-delay-value="25"', false)
        ->assertSee('data-hover-card-side-value="right"', false)
        ->assertSee('data-hover-card-align-value="end"', false)
        ->assertSee('data-hover-card-side-offset-value="12"', false)
        ->assertSee('data-hover-card-align-offset-value="-4"', false)
        ->assertSee('data-hover-card-strategy-value="absolute"', false)
        ->assertSee('data-hover-card-flip-value="false"', false)
        ->assertSee('data-hover-card-shift-value="false"', false)
        ->assertSee('data-side="right"', false)
        ->assertSee('data-align="end"', false);
});

it('starts open when open is true', function () {
    $view = $this->blade('
        <x-hw::hover-card :open="true">
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('data-hover-card-open-value="true"', false)
        ->assertSee('aria-expanded="true"', false)
        ->assertSee('data-open="true"', false);
});

it('renders configurable trigger elements, variants and sizes', function () {
    $view = $this->blade('
        <x-hw::hover-card>
            <x-hw::hover-card.trigger as="a" href="/users/1" variant="ghost" size="sm">Jane Doe</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('<a data-slot="hover-card-trigger"', false)
        ->assertSee('data-variant="ghost"', false)
        ->assertSee('data-size="sm"', false)
        ->assertSee('href="/users/1"', false)
        ->assertSee('</a>', false)
        ->assertDontSee('type="button"', false)
        ->assertDontSee('tabindex="0"', false);
});

it('includes default transitions and can omit them', function () {
    $on = $this->blade('
        <x-hw::hover-card>
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $on->assertSee('data-transition-enter="transition ease-out duration-150"', false)
        ->assertSee('data-transition-enter-from="opacity-0 scale-95"', false)
        ->assertSee('data-transition-leave="transition ease-out duration-150"', false)
        ->assertSee('data-transition-leave-to="block opacity-0 scale-95"', false);

    $off = $this->blade('
        <x-hw::hover-card :transition="false">
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $off->assertDontSee('data-transition-enter', false);
});

it('merges stimulus attributes and filters hover card owned data attributes', function () {
    $view = $this->blade('
        <x-hw::hover-card data-controller="analytics" data-hover-card-side-value="top" :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'hover-card:opened\')">
            <x-hw::hover-card.trigger>User</x-hw::hover-card.trigger>
            <x-hw::hover-card.content>Content</x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('data-controller="hover-card analytics"', false)
        ->assertSee('data-action="hover-card:opened->analytics#track"', false)
        ->assertSee('data-hover-card-side-value="bottom"', false)
        ->assertDontSee('data-hover-card-side-value="top"', false);
});

it('keeps hover card owned trigger and content wiring protected', function () {
    $view = $this->blade('
        <x-hw::hover-card id="protected-hover-card" side="right">
            <x-hw::hover-card.trigger data-action="analytics#track" data-hover-card-target="wrong" aria-expanded="manual">
                User
            </x-hw::hover-card.trigger>

            <x-hw::hover-card.content id="wrong" data-open="manual" data-side="top" data-hover-card-target="wrong">
                Content
            </x-hw::hover-card.content>
        </x-hw::hover-card>
    ');

    $view->assertSee('analytics#track', false)
        ->assertSee('data-hover-card-target="trigger"', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('id="protected-hover-card"', false)
        ->assertSee('data-open="false"', false)
        ->assertSee('data-side="right"', false)
        ->assertSee('data-hover-card-target="content"', false)
        ->assertDontSee('data-hover-card-target="wrong"', false)
        ->assertDontSee('aria-expanded="manual"', false)
        ->assertDontSee('id="wrong"', false)
        ->assertDontSee('data-open="manual"', false)
        ->assertDontSee('data-side="top"', false);
});

it('registers hover card subcomponent aliases', function () {
    expect(ComponentAliases::subComponents())
        ->toHaveKey('hover-card.trigger')
        ->toHaveKey('hover-card.content');
});

it('registers hover card in the catalog with its controller dependency', function () {
    $registry = HotwireRegistry::make();

    expect($registry->component('hover-card')->controllers)->toBe(['hover-card'])
        ->and($registry->controller('hover-card')->npm)->toHaveKey('@floating-ui/dom', '^1.8.0');
});
