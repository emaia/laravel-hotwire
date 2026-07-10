<?php

use Emaia\LaravelHotwire\Components\Drawer;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;

it('renders drawer markup and controller hooks', function () {
    $view = $this->blade('<x-hw::drawer><x-hw::drawer.content>Body</x-hw::drawer.content></x-hw::drawer>');

    $view->assertSee('data-slot="drawer"', false)
        ->assertSee('data-controller="drawer"', false)
        ->assertSee('data-slot="drawer-overlay"', false)
        ->assertSee('data-drawer-target="modal"', false)
        ->assertSee('data-slot="drawer-popup"', false)
        ->assertSee('data-drawer-target="dialog"', false)
        ->assertSee('data-slot="drawer-content"', false)
        ->assertSee('data-direction="down"', false)
        ->assertSee('data-axis="y"', false)
        ->assertSee('role="dialog"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSeeText('Body');
});

it('renders trigger close and semantic content subcomponents', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::drawer direction="right">
            <x-hw::drawer.trigger>Open</x-hw::drawer.trigger>
            <x-hw::drawer.content>
                <x-hw::drawer.header>
                    <x-hw::drawer.title>Title</x-hw::drawer.title>
                    <x-hw::drawer.description>Description</x-hw::drawer.description>
                </x-hw::drawer.header>
                <x-hw::drawer.footer>
                    <x-hw::drawer.close>Close</x-hw::drawer.close>
                </x-hw::drawer.footer>
            </x-hw::drawer.content>
        </x-hw::drawer>
    BLADE);

    $view->assertSee('data-slot="drawer-trigger"', false)
        ->assertSee('data-action="click-&gt;drawer#toggle"', false)
        ->assertSee('data-direction="right"', false)
        ->assertSee('data-axis="x"', false)
        ->assertSee('data-slot="drawer-header"', false)
        ->assertSee('data-slot="drawer-title"', false)
        ->assertSee('data-slot="drawer-description"', false)
        ->assertSee('data-slot="drawer-footer"', false)
        ->assertSee('data-slot="drawer-close"', false)
        ->assertSee('data-action="drawer#close"', false);
});

it('maps side to transform classes and size axis', function () {
    $right = $this->blade('<x-hw::drawer direction="right" size="24rem"><x-hw::drawer.content /></x-hw::drawer>');
    $right->assertSee('data-drawer-dialog-hidden-class="translate-x-full"', false)
        ->assertSee('--drawer-width: 24rem', false)
        ->assertSee('--drawer-max-width: 24rem', false);

    $bottom = $this->blade('<x-hw::drawer side="bottom" size="50vh"><x-hw::drawer.content /></x-hw::drawer>');
    $bottom->assertSee('data-drawer-dialog-hidden-class="translate-y-full"', false)
        ->assertSee('data-direction="down"', false)
        ->assertSee('--drawer-height: 50vh', false);
});

it('throws on an invalid side', function () {
    expect(fn () => new Drawer(side: 'diagonal'))->toThrow(InvalidArgumentException::class);
});

it('registers drawer in the component catalog and subcomponent aliases', function () {
    $drawer = HotwireRegistry::make()->component('drawer');

    expect($drawer->key)->toBe('drawer')
        ->and($drawer->controllers)->toBe(['drawer'])
        ->and($drawer->docs)->toBe('docs/components/drawer.md');

    expect(ComponentAliases::subComponents())
        ->toHaveKey('drawer.trigger')
        ->toHaveKey('drawer.content')
        ->toHaveKey('drawer.close');
});
