<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;

it('renders a shortcut avatar with image and generated fallback initials', function () {
    $view = $this->blade('<x-hw::avatar src="/avatars/dina.jpg" name="Dina Maia" id="user-avatar" />');

    $view->assertSee('<span', false)
        ->assertSee('id="user-avatar"', false)
        ->assertSee('data-slot="avatar"', false)
        ->assertSee('data-size="default"', false)
        ->assertSee('data-shape="circle"', false)
        ->assertSee('data-slot="avatar-image"', false)
        ->assertSee('src="/avatars/dina.jpg"', false)
        ->assertSee('alt="Dina Maia"', false)
        ->assertSee('data-slot="avatar-fallback"', false)
        ->assertSeeText('DM')
        ->assertDontSee('size-8', false);
});

it('renders fallback-only avatars from names, explicit initials, or fallback text', function () {
    $generated = $this->blade('<x-hw::avatar name="Ada Lovelace" />');
    $single = $this->blade('<x-hw::avatar name="Dina" />');
    $initials = $this->blade('<x-hw::avatar name="Dina Maia" initials="EM" />');
    $fallback = $this->blade('<x-hw::avatar fallback="?" />');

    expect((string) $generated)
        ->toContain('data-slot="avatar-fallback"')
        ->toContain('AL')
        ->not->toContain('data-slot="avatar-image"')
        ->and((string) $single)->toContain('DI')
        ->and((string) $initials)->toContain('EM')
        ->and((string) $fallback)->toContain('?');
});

it('renders composed avatar subcomponents', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::avatar size="lg" shape="square">
            <x-hw::avatar.image src="/avatars/ada.jpg" alt="Ada Lovelace" />
            <x-hw::avatar.fallback>AL</x-hw::avatar.fallback>
        </x-hw::avatar>
    BLADE);

    $view->assertSee('data-slot="avatar"', false)
        ->assertSee('data-size="lg"', false)
        ->assertSee('data-shape="square"', false)
        ->assertSee('data-slot="avatar-image"', false)
        ->assertSee('src="/avatars/ada.jpg"', false)
        ->assertSee('alt="Ada Lovelace"', false)
        ->assertSee('data-slot="avatar-fallback"', false)
        ->assertSeeText('AL');
});

it('renders an empty alt attribute when avatar image alt text is omitted', function () {
    $view = $this->blade('<x-hw::avatar><x-hw::avatar.image src="/avatars/guest.jpg" /></x-hw::avatar>');

    $view->assertSee('data-slot="avatar-image"', false)
        ->assertSee('src="/avatars/guest.jpg"', false)
        ->assertSee('alt=""', false);
});

it('renders avatar badge, group and group count subcomponents', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::avatar.group>
            <x-hw::avatar src="/avatars/ana.jpg" name="Ana Silva" />
            <x-hw::avatar name="Dina Maia" size="sm">
                <x-hw::avatar.badge position="top-start" />
            </x-hw::avatar>
            <x-hw::avatar.group-count>+3</x-hw::avatar.group-count>
        </x-hw::avatar.group>
    BLADE);

    $view->assertSee('data-slot="avatar-group"', false)
        ->assertSee('data-slot="avatar-badge"', false)
        ->assertSee('data-position="top-start"', false)
        ->assertSee('data-slot="avatar-group-count"', false)
        ->assertSee('data-size="sm"', false)
        ->assertSeeText('+3');
});

it('renders group count without independent size or shape attributes', function () {
    $html = (string) $this->blade('<x-hw::avatar.group-count>+2</x-hw::avatar.group-count>');

    expect($html)
        ->toContain('data-slot="avatar-group-count"')
        ->not->toContain('data-size=')
        ->not->toContain('data-shape=');
});

it('registers avatar in the component catalog and subcomponent aliases', function () {
    $avatar = HotwireRegistry::make()->component('avatar');

    expect($avatar->key)->toBe('avatar')
        ->and($avatar->controllers)->toBe([])
        ->and($avatar->docs)->toBe('docs/components/avatar.md');

    expect(ComponentAliases::subComponents())
        ->toHaveKey('avatar.image')
        ->toHaveKey('avatar.fallback')
        ->toHaveKey('avatar.badge')
        ->toHaveKey('avatar.group')
        ->toHaveKey('avatar.group-count');
});
