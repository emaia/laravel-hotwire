<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;

it('renders popover controller, trigger and content wiring', function () {
    $view = $this->blade('
        <x-hw::popover>
            <x-hw::popover.trigger>Details</x-hw::popover.trigger>
            <x-hw::popover.content><p>Panel content</p></x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-slot="popover"', false)
        ->assertSee('data-controller="popover"', false)
        ->assertSee('data-slot="popover-trigger"', false)
        ->assertSee('data-popover-target="trigger"', false)
        ->assertSee('data-action="popover#toggle"', false)
        ->assertSee('aria-haspopup="dialog"', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('data-slot="popover-content"', false)
        ->assertSee('data-popover-target="content"', false)
        ->assertSee('role="dialog"', false)
        ->assertSee('tabindex="-1"', false)
        ->assertSee('Details')
        ->assertSee('Panel content');
});

it('links the trigger and content via id and aria-controls', function () {
    $view = $this->blade('
        <x-hw::popover id="account-popover">
            <x-hw::popover.trigger>Account</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('id="account-popover"', false)
        ->assertSee('aria-controls="account-popover"', false);
});

it('auto-generates a content id when none is given', function () {
    $view = $this->blade('
        <x-hw::popover>
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('id="popover-', false)
        ->assertSee('aria-controls="popover-', false);
});

it('emits positioning defaults for Floating UI', function () {
    $view = $this->blade('
        <x-hw::popover>
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-popover-side-value="bottom"', false)
        ->assertSee('data-popover-align-value="start"', false)
        ->assertSee('data-popover-side-offset-value="4"', false)
        ->assertSee('data-popover-align-offset-value="0"', false)
        ->assertSee('data-popover-strategy-value="fixed"', false)
        ->assertSee('data-popover-flip-value="true"', false)
        ->assertSee('data-popover-shift-value="true"', false)
        ->assertSee('data-side="bottom"', false)
        ->assertSee('data-align="start"', false);
});

it('emits custom positioning values', function () {
    $view = $this->blade('
        <x-hw::popover side="right" align="end" :side-offset="12" :align-offset="-4" strategy="absolute" :flip="false" :shift="false">
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-popover-side-value="right"', false)
        ->assertSee('data-popover-align-value="end"', false)
        ->assertSee('data-popover-side-offset-value="12"', false)
        ->assertSee('data-popover-align-offset-value="-4"', false)
        ->assertSee('data-popover-strategy-value="absolute"', false)
        ->assertSee('data-popover-flip-value="false"', false)
        ->assertSee('data-popover-shift-value="false"', false)
        ->assertSee('data-side="right"', false)
        ->assertSee('data-align="end"', false);
});

it('starts open when open is true', function () {
    $view = $this->blade('
        <x-hw::popover :open="true">
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-popover-open-value="true"', false)
        ->assertSee('aria-expanded="true"', false)
        ->assertSee('data-open="true"', false);
});

it('includes default transitions and can omit them', function () {
    $on = $this->blade('
        <x-hw::popover>
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $on->assertSee('data-transition-enter="transition ease-out duration-150"', false)
        ->assertSee('data-transition-enter-from="opacity-0 scale-95"', false)
        ->assertSee('data-transition-leave="transition ease-out duration-150"', false)
        ->assertSee('data-transition-leave-to="block opacity-0 scale-95"', false);

    $off = $this->blade('
        <x-hw::popover :transition="false">
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $off->assertDontSee('data-transition-enter', false);
});

it('merges stimulus attributes and filters popover-owned data attributes', function () {
    $view = $this->blade('
        <x-hw::popover data-controller="analytics" data-popover-side-value="top" :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'popover:opened\')">
            <x-hw::popover.trigger>Open</x-hw::popover.trigger>
            <x-hw::popover.content>Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-controller="popover analytics"', false)
        ->assertSee('data-action="popover:opened->analytics#track"', false)
        ->assertSee('data-popover-side-value="bottom"', false)
        ->assertDontSee('data-popover-side-value="top"', false);
});

it('merges trigger and content attributes', function () {
    $view = $this->blade('
        <x-hw::popover>
            <x-hw::popover.trigger class="justify-between btn-outline">Open</x-hw::popover.trigger>
            <x-hw::popover.content class="w-80 p-6 text-sm">Content</x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-slot="popover-trigger"', false)
        ->assertSee('justify-between', false)
        ->assertSee('btn-outline', false)
        ->assertSee('w-80 p-6 text-sm', false);
});

it('keeps popover-owned trigger and content wiring protected', function () {
    $view = $this->blade('
        <x-hw::popover id="protected-popover" side="right">
            <x-hw::popover.trigger data-action="analytics#track" data-popover-target="wrong" aria-expanded="manual">
                Open
            </x-hw::popover.trigger>

            <x-hw::popover.content id="wrong" data-open="manual" data-side="top" data-popover-target="wrong">
                Content
            </x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-action="popover#toggle analytics#track"', false)
        ->assertSee('data-popover-target="trigger"', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('id="protected-popover"', false)
        ->assertSee('data-open="false"', false)
        ->assertSee('data-side="right"', false)
        ->assertSee('data-popover-target="content"', false)
        ->assertDontSee('data-popover-target="wrong"', false)
        ->assertDontSee('aria-expanded="manual"', false)
        ->assertDontSee('id="wrong"', false)
        ->assertDontSee('data-open="manual"', false)
        ->assertDontSee('data-side="top"', false);
});

it('renders optional header, title and description subcomponents', function () {
    $view = $this->blade('
        <x-hw::popover>
            <x-hw::popover.trigger>Edit profile</x-hw::popover.trigger>

            <x-hw::popover.content>
                <x-hw::popover.header>
                    <x-hw::popover.title>Profile</x-hw::popover.title>
                    <x-hw::popover.description>Update public profile details.</x-hw::popover.description>
                </x-hw::popover.header>
            </x-hw::popover.content>
        </x-hw::popover>
    ');

    $view->assertSee('data-slot="popover-header"', false)
        ->assertSee('data-slot="popover-title"', false)
        ->assertSee('data-slot="popover-description"', false)
        ->assertSeeText('Profile')
        ->assertSeeText('Update public profile details.');
});

it('registers popover subcomponent aliases', function () {
    expect(ComponentAliases::subComponents())
        ->toHaveKey('popover.trigger')
        ->toHaveKey('popover.content')
        ->toHaveKey('popover.header')
        ->toHaveKey('popover.title')
        ->toHaveKey('popover.description');
});

it('registers popover in the catalog with its controller dependency', function () {
    $registry = HotwireRegistry::make();

    expect($registry->component('popover')->controllers)->toBe(['popover'])
        ->and($registry->controller('popover')->npm)->toHaveKey('@floating-ui/dom', '^1.8.0');
});
