<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;

it('renders the controller, trigger button and menu wiring', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-hw::dropdown.trigger>Options</x-hw::dropdown.trigger>
            <x-hw::dropdown.content>
                <a href="/account">Account</a>
            </x-hw::dropdown.content>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-controller="dropdown"', false);
    $view->assertSee('data-dropdown-target="trigger"', false);
    $view->assertSee('data-action="dropdown#toggle"', false);
    $view->assertSee('aria-haspopup="true"', false);
    $view->assertSee('aria-expanded="false"', false);
    $view->assertSee('data-state="closed"', false);
    $view->assertSee('data-dropdown-target="menu"', false);
    $view->assertSee('Options');
    $view->assertSee('href="/account"', false);
});

it('renders dropdown subcomponents with short tag syntax', function () {
    $view = $this->blade('
        <hw:dropdown id="short-menu">
            <hw:dropdown.trigger>Short</hw:dropdown.trigger>
            <hw:dropdown.content>
                <hw:dropdown.item href="/short">Short item</hw:dropdown.item>
            </hw:dropdown.content>
        </hw:dropdown>
    ');

    $view->assertSee('data-slot="dropdown-trigger"', false);
    $view->assertSee('data-slot="dropdown-menu"', false);
    $view->assertSee('aria-controls="short-menu"', false);
    $view->assertSee('href="/short"', false);
});

it('uses an existing child component as the trigger when requested', function () {
    $view = $this->blade('
        <x-hw::dropdown id="team-menu">
            <x-hw::dropdown.trigger as-child data-action="analytics#track">
                <x-hw::sidebar.menu-button size="lg" class="team-trigger">
                    <span>Acme</span>
                </x-hw::sidebar.menu-button>
            </x-hw::dropdown.trigger>

            <x-hw::dropdown.content side="right" mobile-side="bottom">
                <x-hw::dropdown.item>Team settings</x-hw::dropdown.item>
            </x-hw::dropdown.content>
        </x-hw::dropdown>
    ');

    $html = (string) $view;

    expect(countElements($html, '//*[@data-slot="dropdown-trigger"]'))->toBe(0);

    $view->assertSee('data-slot="sidebar-menu-button"', false)
        ->assertSee('<button type="button"', false)
        ->assertSee('data-dropdown-target="trigger"', false)
        ->assertSee('data-action="dropdown#toggle analytics#track"', false)
        ->assertSee('aria-haspopup="true"', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('aria-controls="team-menu"', false)
        ->assertSee('data-state="closed"', false)
        ->assertSee('team-trigger', false)
        ->assertDontSee('<buttontype', false)
        ->assertDontSee('data-slot="dropdown-trigger"', false);
});

it('does not render a trigger when no trigger subcomponent is provided', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-hw::dropdown.content>
                <a href="/x">x</a>
            </x-hw::dropdown.content>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-controller="dropdown"', false);
    $view->assertDontSee('data-dropdown-target="trigger"', false);
    $view->assertSee('href="/x"', false);
});

it('links the trigger to the menu via id and aria-controls', function () {
    $view = $this->blade('
        <x-hw::dropdown id="acct">
            <x-hw::dropdown.trigger>M</x-hw::dropdown.trigger>
            <x-hw::dropdown.content><a href="/x">x</a></x-hw::dropdown.content>
        </x-hw::dropdown>
    ');

    $view->assertSee('id="acct"', false);
    $view->assertSee('aria-controls="acct"', false);
});

it('auto-generates a menu id when none is given', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-hw::dropdown.trigger>M</x-hw::dropdown.trigger>
            <x-hw::dropdown.content><a href="/x">x</a></x-hw::dropdown.content>
        </x-hw::dropdown>
    ');

    $view->assertSee('id="dropdown-', false);
    $view->assertSee('aria-controls="dropdown-', false);
});

it('is hidden and closed by default', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-hw::dropdown.trigger>M</x-hw::dropdown.trigger>
            <x-hw::dropdown.content><a href="/x">x</a></x-hw::dropdown.content>
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
            <x-hw::dropdown.trigger>M</x-hw::dropdown.trigger>
            <x-hw::dropdown.content><a href="/x">x</a></x-hw::dropdown.content>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-dropdown-open-value="true"', false);
    $view->assertSee('aria-expanded="true"', false);
    $view->assertSee('data-state="open"', false);
    $view->assertSee('data-open="true"', false);
});

it('emits dropdown positioning defaults from content', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-hw::dropdown.trigger>M</x-hw::dropdown.trigger>
            <x-hw::dropdown.content><a href="/x">x</a></x-hw::dropdown.content>
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
});

it('emits custom and adaptive dropdown positioning values from content', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-hw::dropdown.trigger>M</x-hw::dropdown.trigger>
            <x-hw::dropdown.content side="right" align="end" :side-offset="12" :align-offset="-4" strategy="fixed" :flip="false" :shift="false" mobile-side="bottom" mobile-align="center" collapsed-side="right" collapsed-align="start">
                <a href="/x">x</a>
            </x-hw::dropdown.content>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-dropdown-side-value="right"', false);
    $view->assertSee('data-dropdown-align-value="end"', false);
    $view->assertSee('data-dropdown-side-offset-value="12"', false);
    $view->assertSee('data-dropdown-align-offset-value="-4"', false);
    $view->assertSee('data-dropdown-strategy-value="fixed"', false);
    $view->assertSee('data-dropdown-flip-value="false"', false);
    $view->assertSee('data-dropdown-shift-value="false"', false);
    $view->assertSee('data-dropdown-mobile-side-value="bottom"', false);
    $view->assertSee('data-dropdown-mobile-align-value="center"', false);
    $view->assertSee('data-dropdown-collapsed-side-value="right"', false);
    $view->assertSee('data-dropdown-collapsed-align-value="start"', false);
    $view->assertSee('data-dropdown-collapsed-when-value="[data-slot=sidebar][data-collapsible=icon], [data-slot=sidebar][data-state=collapsed]', false);
    $view->assertSee('data-side="right"', false);
    $view->assertSee('data-align="end"', false);
});

it('omits close-on-select by default and emits it when disabled', function () {
    $default = $this->blade('
        <x-hw::dropdown>
            <x-hw::dropdown.trigger>M</x-hw::dropdown.trigger>
            <x-hw::dropdown.content><a href="/x">x</a></x-hw::dropdown.content>
        </x-hw::dropdown>
    ');
    $default->assertDontSee('data-dropdown-close-on-select-value', false);

    $off = $this->blade('
        <x-hw::dropdown :close-on-select="false">
            <x-hw::dropdown.trigger>M</x-hw::dropdown.trigger>
            <x-hw::dropdown.content><a href="/x">x</a></x-hw::dropdown.content>
        </x-hw::dropdown>
    ');
    $off->assertSee('data-dropdown-close-on-select-value="false"', false);

    $dom = dom((string) $off);
    $xpath = new DOMXPath($dom);
    $root = $xpath->query('//*[@data-controller="dropdown"]')->item(0);
    $menu = $xpath->query('//*[@data-dropdown-target="menu"]')->item(0);

    expect($root?->getAttribute('data-dropdown-close-on-select-value'))->toBe('false')
        ->and($menu?->hasAttribute('data-dropdown-close-on-select-value'))->toBeFalse();
});

it('includes default transitions, and omits them when disabled', function () {
    $on = $this->blade('
        <x-hw::dropdown>
            <x-hw::dropdown.trigger>M</x-hw::dropdown.trigger>
            <x-hw::dropdown.content><a href="/x">x</a></x-hw::dropdown.content>
        </x-hw::dropdown>
    ');
    $on->assertSee('data-transition-enter-from="opacity-0 scale-95"', false);

    $off = $this->blade('
        <x-hw::dropdown>
            <x-hw::dropdown.trigger>M</x-hw::dropdown.trigger>
            <x-hw::dropdown.content :transition="false"><a href="/x">x</a></x-hw::dropdown.content>
        </x-hw::dropdown>
    ');
    $off->assertDontSee('data-transition-enter', false);
});

it('unions a user-supplied data-controller', function () {
    $view = $this->blade('
        <x-hw::dropdown data-controller="analytics">
            <x-hw::dropdown.trigger>M</x-hw::dropdown.trigger>
            <x-hw::dropdown.content><a href="/x">x</a></x-hw::dropdown.content>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-controller="dropdown analytics"', false);
});

it('merges inline stimulus attributes with the dropdown controller', function () {
    $view = $this->blade('
        <x-hw::dropdown :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'dropdown:opened\')">
            <x-hw::dropdown.trigger>M</x-hw::dropdown.trigger>
            <x-hw::dropdown.content><a href="/x">x</a></x-hw::dropdown.content>
        </x-hw::dropdown>
    ');

    $view->assertSee('data-controller="dropdown analytics"', false);
    $view->assertSee('data-action="dropdown:opened->analytics#track"', false);
});

it('filters user-supplied data-dropdown-* attributes', function () {
    $view = $this->blade('
        <x-hw::dropdown data-dropdown-foo="bar">
            <x-hw::dropdown.trigger>M</x-hw::dropdown.trigger>
            <x-hw::dropdown.content><a href="/x">x</a></x-hw::dropdown.content>
        </x-hw::dropdown>
    ');

    $view->assertDontSee('data-dropdown-foo', false);
});

it('merges trigger attributes onto the button', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-hw::dropdown.trigger class="btn-primary">Options</x-hw::dropdown.trigger>
            <x-hw::dropdown.content><a href="/x">x</a></x-hw::dropdown.content>
        </x-hw::dropdown>
    ');

    $view->assertSee('btn-primary', false);
    $view->assertSee('data-slot="dropdown-trigger"', false);
});

it('overrides menu width and accepts extra menu classes on content', function () {
    $view = $this->blade('
        <x-hw::dropdown>
            <x-hw::dropdown.trigger>M</x-hw::dropdown.trigger>
            <x-hw::dropdown.content width="w-72" class="text-sm"><a href="/x">x</a></x-hw::dropdown.content>
        </x-hw::dropdown>
    ');

    $view->assertSee('w-72', false);
    $view->assertSee('text-sm', false);
});

it('renders composed menu subcomponents', function () {
    $view = $this->blade('
        <x-hw::dropdown id="account-menu">
            <x-hw::dropdown.trigger>Account</x-hw::dropdown.trigger>

            <x-hw::dropdown.content>
                <x-hw::dropdown.group>
                    <x-hw::dropdown.label>Workspace</x-hw::dropdown.label>
                    <x-hw::dropdown.item href="/settings">
                        Settings
                        <x-hw::dropdown.shortcut>Cmd+,</x-hw::dropdown.shortcut>
                    </x-hw::dropdown.item>
                    <x-hw::dropdown.separator />
                    <x-hw::dropdown.item variant="destructive">Sign out</x-hw::dropdown.item>
                </x-hw::dropdown.group>
            </x-hw::dropdown.content>
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
            <x-hw::dropdown.trigger>Actions</x-hw::dropdown.trigger>
            <x-hw::dropdown.content>
                <x-hw::dropdown.item href="/billing" disabled inset data-test="link">Billing</x-hw::dropdown.item>
                <x-hw::dropdown.item type="submit" data-test="button">Save</x-hw::dropdown.item>
            </x-hw::dropdown.content>
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
        ->toHaveKey('dropdown.trigger')
        ->toHaveKey('dropdown.content')
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

function dom(string $html): DOMDocument
{
    $dom = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    return $dom;
}

function countElements(string $html, string $query): int
{
    return (new DOMXPath(dom($html)))->query($query)->count();
}
