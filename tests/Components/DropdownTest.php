<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;

it('renders the controller, trigger button and menu wiring', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-slot:trigger>Options</x-slot:trigger>
            <a href="/account">Account</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-controller="dropdown"', false);
    $view->assertSee('data-dropdown-target="trigger"', false);
    $view->assertSee('data-action="dropdown#toggle"', false);
    $view->assertSee('aria-haspopup="true"', false);
    $view->assertSee('aria-expanded="false"', false);
    $view->assertSee('data-dropdown-target="menu"', false);
    $view->assertSee('Options');
    $view->assertSee('href="/account"', false);
});

it('does not crash when no trigger slot is provided', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-controller="dropdown"', false);
    $view->assertSee('data-dropdown-target="trigger"', false); // button is still rendered and wired
    $view->assertSee('href="/x"', false);
});

it('links the trigger to the menu via id and aria-controls', function () {
    $view = $this->blade('
        <x-hw::dropdown id="acct">
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('id="acct"', false);
    $view->assertSee('aria-controls="acct"', false);
});

it('auto-generates a menu id when none is given', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('id="dropdown-', false);
    $view->assertSee('aria-controls="dropdown-', false);
});

it('emits semantic popover hooks for the menu', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-slot="dropdown-menu"', false);
    $view->assertSee('data-open="false"', false);
    $view->assertDontSee('bg-popover', false);
    $view->assertDontSee('shadow-md', false);
    $view->assertDontSee('overflow-y-auto', false);
});

it('is hidden and closed by default', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-open="false"', false);
    $view->assertSee('aria-expanded="false"', false);
    $view->assertDontSee('data-dropdown-open-value', false);
    expect((string) $view)->not->toMatch('/[\s"]hidden[\s"]/');
});

it('starts open when open is true', function () {
    $view = $this->blade('
        <x-hw::dropdown :open="true">
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-dropdown-open-value="true"', false);
    $view->assertSee('aria-expanded="true"', false);
    $view->assertSee('data-open="true"', false);
    expect((string) $view)->not->toMatch('/[\s"]hidden[\s"]/');
});

it('aligns to the start by default (RTL-safe logical position)', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-align="start"', false);
    $view->assertDontSee('data-align="end"', false);
});

it('emits dropdown positioning defaults for anchored placement', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-dropdown-side-value="bottom"', false);
    $view->assertSee('data-dropdown-align-value="start"', false);
    $view->assertSee('data-dropdown-side-offset-value="4"', false);
    $view->assertSee('data-dropdown-align-offset-value="0"', false);
    $view->assertSee('data-dropdown-strategy-value="absolute"', false);
    $view->assertSee('data-dropdown-flip-value="true"', false);
    $view->assertSee('data-dropdown-shift-value="true"', false);
    $view->assertSee('data-side="bottom"', false);
    $view->assertSee('data-align="start"', false);
    $view->assertDontSee('data-width="default"', false);
});

it('emits custom dropdown positioning values', function () {
    $view = $this->blade('
        <x-hw::dropdown side="right" align="end" :side-offset="12" :align-offset="-4" strategy="fixed" :flip="false" :shift="false">
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-dropdown-side-value="right"', false);
    $view->assertSee('data-dropdown-align-value="end"', false);
    $view->assertSee('data-dropdown-side-offset-value="12"', false);
    $view->assertSee('data-dropdown-align-offset-value="-4"', false);
    $view->assertSee('data-dropdown-strategy-value="fixed"', false);
    $view->assertSee('data-dropdown-flip-value="false"', false);
    $view->assertSee('data-dropdown-shift-value="false"', false);
    $view->assertSee('data-side="right"', false);
    $view->assertSee('data-align="end"', false);
});

it('aligns to the end when requested', function () {
    $view = $this->blade('
        <x-hw::dropdown align="end">
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-align="end"', false);
    $view->assertDontSee('data-align="start"', false);
});

it('omits close-on-select by default and emits it when disabled', function () {
    $default = $this->blade('
        <x-hw::dropdown>
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');
    $default->assertDontSee('data-dropdown-close-on-select-value', false);

    $off = $this->blade('
        <x-hw::dropdown :close-on-select="false">
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');
    $off->assertSee('data-dropdown-close-on-select-value="false"', false);

    $dom = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>'.(string) $off);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $xpath = new DOMXPath($dom);
    $root = $xpath->query('//*[@data-controller="dropdown"]')->item(0);
    $menu = $xpath->query('//*[@data-dropdown-target="menu"]')->item(0);

    expect($root?->getAttribute('data-dropdown-close-on-select-value'))->toBe('false')
        ->and($menu?->hasAttribute('data-dropdown-close-on-select-value'))->toBeFalse();
});

it('includes default transitions, and omits them when disabled', function () {
    $on = $this->blade('
        <x-hw::dropdown>
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');
    $on->assertSee('data-transition-enter-from="opacity-0 scale-95"', false);

    $off = $this->blade('
        <x-hw::dropdown :transition="false">
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');
    $off->assertDontSee('data-transition-enter', false);
});

it('unions a user-supplied data-controller', function () {
    $view = $this->blade('
        <x-hw::dropdown data-controller="analytics">
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-controller="dropdown analytics"', false);
});

it('merges inline stimulus attributes with the dropdown controller', function () {
    $view = $this->blade('
        <x-hw::dropdown :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'dropdown:opened\')">
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-controller="dropdown analytics"', false);
    $view->assertSee('data-action="dropdown:opened->analytics#track"', false);
});

it('filters user-supplied data-dropdown-* attributes', function () {
    $view = $this->blade('
        <x-hw::dropdown data-dropdown-foo="bar">
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertDontSee('data-dropdown-foo', false);
});

it('merges trigger slot attributes onto the button', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-slot:trigger class="btn-primary">Options</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('btn-primary', false);
    $view->assertSee('data-slot="dropdown-trigger"', false);
});

it('overrides the trigger layout classes while keeping the semantic trigger hook', function () {
    $view = $this->blade('
        <x-hw::dropdown trigger-class="flex w-full justify-between">
            <x-slot:trigger class="btn-outline">M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('flex w-full justify-between', false); // default layout replaced
    $view->assertSee('btn-outline', false);                 // slot class still appended
    $view->assertSee('data-slot="dropdown-trigger"', false);
    $view->assertDontSee('inline-flex', false);             // preset default is not emitted by Blade
});

it('overrides menu width and accepts extra menu classes', function () {
    $view = $this->blade('
        <x-hw::dropdown width="w-72" menu-class="text-sm">
            <x-slot:trigger>M</x-slot:trigger>
            <a href="/x">x</a>
        </x-hw::dropdown>
    ');

    $view->assertSee('w-72', false);
    $view->assertDontSee('w-56', false);
    $view->assertSee('text-sm', false);
});

it('renders composed menu subcomponents', function () {
    $view = $this->blade('
        <x-hw::dropdown id="account-menu">
            <x-slot:trigger>Account</x-slot:trigger>

            <x-hw::dropdown.group>
                <x-hw::dropdown.label>Workspace</x-hw::dropdown.label>
                <x-hw::dropdown.item href="/settings">
                    Settings
                    <x-hw::dropdown.shortcut>Cmd+,</x-hw::dropdown.shortcut>
                </x-hw::dropdown.item>
                <x-hw::dropdown.separator />
                <x-hw::dropdown.item variant="destructive">Sign out</x-hw::dropdown.item>
            </x-hw::dropdown.group>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-slot="dropdown-group"', false);
    $view->assertSee('role="group"', false);
    $view->assertSee('data-slot="dropdown-label"', false);
    $view->assertSee('data-slot="dropdown-item"', false);
    $view->assertSee('href="/settings"', false);
    $view->assertSee('data-slot="dropdown-shortcut"', false);
    $view->assertSee('data-slot="dropdown-separator"', false);
    $view->assertSee('role="separator"', false);
    $view->assertSee('data-variant="destructive"', false);
});

it('renders dropdown items as links or buttons with state hooks', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-slot:trigger>Actions</x-slot:trigger>
            <x-hw::dropdown.item href="/billing" disabled inset data-test="link">Billing</x-hw::dropdown.item>
            <x-hw::dropdown.item type="submit" data-test="button">Save</x-hw::dropdown.item>
        </x-hw::dropdown>
    ');

    $view->assertSee('<a', false);
    $view->assertSee('href="/billing"', false);
    $view->assertSee('data-disabled="true"', false);
    $view->assertSee('aria-disabled="true"', false);
    $view->assertSee('tabindex="-1"', false);
    $view->assertSee('data-inset="true"', false);
    $view->assertSee('<button', false);
    $view->assertSee('type="submit"', false);
    $view->assertSee('data-test="button"', false);
});

it('registers dropdown subcomponent aliases', function () {
    expect(ComponentAliases::subComponents())
        ->toHaveKey('dropdown.item')
        ->toHaveKey('dropdown.label')
        ->toHaveKey('dropdown.separator')
        ->toHaveKey('dropdown.shortcut')
        ->toHaveKey('dropdown.group');
});

it('registers Floating UI as the dropdown controller dependency', function () {
    expect(HotwireRegistry::make()->controller('dropdown')->npm)
        ->toHaveKey('@floating-ui/dom', '^1.8.0');
});
