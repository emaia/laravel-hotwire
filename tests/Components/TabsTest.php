<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;

it('renders the tabs root with controller and slot hooks', function () {
    $view = $this->blade('<x-hw::tabs id="settings"><x-hw::tabs.list /></x-hw::tabs>');

    $view->assertSee('id="settings"', false)
        ->assertSee('data-slot="tabs"', false)
        ->assertSee('data-orientation="horizontal"', false)
        ->assertSee('data-controller="tabs"', false)
        ->assertDontSee('rounded-lg', false);
});

it('merges user controllers on the root', function () {
    $view = $this->blade('<x-hw::tabs id="settings" data-controller="tab-url">Content</x-hw::tabs>');

    $view->assertSee('data-controller="tabs tab-url"', false);
});

it('merges inline stimulus attributes with the internal tabs controller', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::tabs
            id="settings"
            active="profile"
            :stimulus="stimulus()->controller('tab-url')->action('tab-url', 'update', 'tabs:change')"
        >
            <x-hw::tabs.list variant="line" aria-label="Settings">
                <x-hw::tabs.trigger value="profile" data-tab-name="profile">Profile</x-hw::tabs.trigger>
                <x-hw::tabs.trigger value="billing" data-tab-name="billing">Billing</x-hw::tabs.trigger>
            </x-hw::tabs.list>
        </x-hw::tabs>
    BLADE);

    $view->assertSee('data-controller="tabs tab-url"', false)
        ->assertSee('data-action="tabs:change->tab-url#update"', false);
});

it('merges inline stimulus attributes on tabs list with internal actions', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::tabs id="settings">
            <x-hw::tabs.list
                aria-label="Settings"
                :stimulus="stimulus()->action('dev--log', 'log', 'focusin')"
            />
        </x-hw::tabs>
    BLADE);

    $view->assertSee('data-action="click->tabs#select keydown->tabs#navigate focusin->dev--log#log"', false);
});

it('renders inline stimulus attributes on tabs triggers', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::tabs id="settings" active="profile">
            <x-hw::tabs.list aria-label="Settings">
                <x-hw::tabs.trigger
                    value="profile"
                    data-tab-name="profile"
                    :stimulus="stimulus()->controller('dev--log')->action('dev--log', 'log')"
                >
                    Profile
                </x-hw::tabs.trigger>
            </x-hw::tabs.list>
        </x-hw::tabs>
    BLADE);

    $view->assertSee('data-controller="dev--log"', false)
        ->assertSee('data-action="dev--log#log"', false)
        ->assertSee('data-tab-name="profile"', false);
});

it('renders inline stimulus attributes on tabs panels', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::tabs id="settings" active="profile">
            <x-hw::tabs.panel
                value="profile"
                :stimulus="stimulus()->controller('panel-analytics', ['name' => 'profile'])->target('panel-analytics', 'panel')"
            >
                Profile settings
            </x-hw::tabs.panel>
        </x-hw::tabs>
    BLADE);

    $view->assertSee('data-controller="panel-analytics"', false)
        ->assertSee('data-panel-analytics-name-value="profile"', false)
        ->assertSee('data-panel-analytics-target="panel"', false);
});

it('renders a tab list with delegated actions and orientation', function () {
    $view = $this->blade('<x-hw::tabs id="settings"><x-hw::tabs.list aria-label="Settings" orientation="vertical" /></x-hw::tabs>');

    $view->assertSee('data-slot="tabs-list"', false)
        ->assertSee('data-variant="default"', false)
        ->assertSee('role="tablist"', false)
        ->assertSee('aria-label="Settings"', false)
        ->assertSee('aria-orientation="vertical"', false)
        ->assertSee('data-action="click->tabs#select keydown->tabs#navigate"', false);
});

it('renders the line tab list variant', function () {
    $view = $this->blade('<x-hw::tabs id="settings"><x-hw::tabs.list variant="line" aria-label="Settings" /></x-hw::tabs>');

    $view->assertSee('data-slot="tabs-list"', false)
        ->assertSee('data-variant="line"', false)
        ->assertSee('role="tablist"', false);
});

it('derives matching trigger and panel ids from value', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::tabs id="settings">
            <x-hw::tabs.list aria-label="Settings">
                <x-hw::tabs.trigger value="profile">Profile</x-hw::tabs.trigger>
            </x-hw::tabs.list>
            <x-hw::tabs.panel value="profile">Profile settings</x-hw::tabs.panel>
        </x-hw::tabs>
    BLADE);

    $view->assertSee('id="settings-tab-profile"', false)
        ->assertSee('aria-controls="settings-panel-profile"', false)
        ->assertSee('id="settings-panel-profile"', false)
        ->assertSee('aria-labelledby="settings-tab-profile"', false)
        ->assertSeeText('Profile settings');
});

it('server-renders the active tab and hides inactive panels', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::tabs id="settings" active="billing">
            <x-hw::tabs.list aria-label="Settings">
                <x-hw::tabs.trigger value="profile">Profile</x-hw::tabs.trigger>
                <x-hw::tabs.trigger value="billing">Billing</x-hw::tabs.trigger>
            </x-hw::tabs.list>
            <x-hw::tabs.panel value="profile">Profile settings</x-hw::tabs.panel>
            <x-hw::tabs.panel value="billing">Billing settings</x-hw::tabs.panel>
        </x-hw::tabs>
    BLADE);

    expect((string) $view)->toMatch('/<button[^>]*id="settings-tab-profile"[^>]*aria-selected="false"/');
    expect((string) $view)->toMatch('/<button[^>]*id="settings-tab-profile"[^>]*tabindex="-1"/');
    expect((string) $view)->toMatch('/<button[^>]*id="settings-tab-billing"[^>]*aria-selected="true"/');
    expect((string) $view)->toMatch('/<button[^>]*id="settings-tab-billing"[^>]*tabindex="0"/');
    expect((string) $view)->toMatch('/<button[^>]*id="settings-tab-profile"[^>]*data-state="inactive"/');
    expect((string) $view)->toMatch('/<button[^>]*id="settings-tab-billing"[^>]*data-state="active"/');
    expect((string) $view)->toMatch('/<div[^>]*id="settings-panel-profile"[^>]*hidden/');
    expect((string) $view)->toMatch('/<div[^>]*id="settings-panel-profile"[^>]*data-state="inactive"/');
    expect((string) $view)->toMatch('/<div[^>]*id="settings-panel-billing"[^>]*data-state="active"/');
    expect((string) $view)->not()->toMatch('/<div[^>]*id="settings-panel-billing"[^>]*hidden/');
});

it('renders vertical orientation from the tabs root', function () {
    $view = $this->blade('<x-hw::tabs id="settings" orientation="vertical"><x-hw::tabs.list aria-label="Settings" /></x-hw::tabs>');

    $view->assertSee('data-orientation="vertical"', false)
        ->assertSee('aria-orientation="vertical"', false);
});

it('emits selected index value when provided', function () {
    $view = $this->blade('<x-hw::tabs id="settings" :selected-index="2">Content</x-hw::tabs>');

    $view->assertSee('data-tabs-selected-index-value="2"', false);
});

it('swaps the Stimulus identifier when controller prop is set', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::tabs id="settings" controller="settings-tabs" :selected-index="1" data-settings-tabs-delay-value="100" data-settings-tabs-selected-index-value="9">
            <x-hw::tabs.list aria-label="Settings">
                <x-hw::tabs.trigger value="profile" data-settings-tabs-delay-value="200" data-settings-tabs-target="override">Profile</x-hw::tabs.trigger>
            </x-hw::tabs.list>
            <x-hw::tabs.panel value="profile" data-settings-tabs-delay-value="300" data-settings-tabs-target="override">Profile settings</x-hw::tabs.panel>
        </x-hw::tabs>
    BLADE);

    $view->assertSee('data-controller="settings-tabs"', false)
        ->assertSee('data-settings-tabs-selected-index-value="1"', false)
        ->assertSee('data-action="click->settings-tabs#select keydown->settings-tabs#navigate"', false)
        ->assertSee('data-settings-tabs-target="tab"', false)
        ->assertSee('data-settings-tabs-target="panel"', false)
        ->assertSee('data-settings-tabs-delay-value="100"', false)
        ->assertSee('data-settings-tabs-delay-value="200"', false)
        ->assertSee('data-settings-tabs-delay-value="300"', false)
        ->assertDontSee('data-settings-tabs-selected-index-value="9"', false)
        ->assertDontSee('data-settings-tabs-target="override"', false);
});

it('lets trigger and panel ids be overridden', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::tabs id="settings" active="profile">
            <x-hw::tabs.list aria-label="Settings">
                <x-hw::tabs.trigger value="profile" id="profile-tab" aria-controls="profile-panel">Profile</x-hw::tabs.trigger>
            </x-hw::tabs.list>
            <x-hw::tabs.panel value="profile" id="profile-panel" aria-labelledby="profile-tab">Profile settings</x-hw::tabs.panel>
        </x-hw::tabs>
    BLADE);

    $view->assertSee('id="profile-tab"', false)
        ->assertSee('aria-controls="profile-panel"', false)
        ->assertSee('id="profile-panel"', false)
        ->assertSee('aria-labelledby="profile-tab"', false);
});

it('renders disabled tab triggers', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::tabs id="settings">
            <x-hw::tabs.list aria-label="Settings">
                <x-hw::tabs.trigger value="profile" disabled>Profile</x-hw::tabs.trigger>
            </x-hw::tabs.list>
        </x-hw::tabs>
    BLADE);

    expect((string) $view)->toMatch('/<button[^>]*id="settings-tab-profile"[^>]*disabled[^>]*aria-disabled="true"[^>]*tabindex="-1"/');
});

it('renders icon content inside tab triggers', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::tabs id="settings">
            <x-hw::tabs.list aria-label="Settings">
                <x-hw::tabs.trigger value="preview">
                    <x-hw::icon name="app-window" />
                    Preview
                </x-hw::tabs.trigger>
            </x-hw::tabs.list>
        </x-hw::tabs>
    BLADE);

    $view->assertSee('data-slot="icon"', false)
        ->assertSeeText('Preview');
});

it('registers tabs in the component catalog', function () {
    $tabs = HotwireRegistry::make()->component('tabs');

    expect($tabs->key)->toBe('tabs')
        ->and($tabs->controllers)->toBe(['tabs'])
        ->and($tabs->docs)->toBe('docs/components/tabs.md');
});
