<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;

it('renders a sidebar provider with controller state and layout hooks', function () {
    $view = $this->blade('<x-hw::sidebar.provider id="app-shell"><x-hw::sidebar>Nav</x-hw::sidebar><x-hw::sidebar.inset>Main</x-hw::sidebar.inset></x-hw::sidebar.provider>');

    $view->assertSee('id="app-shell"', false)
        ->assertSee('data-slot="sidebar-wrapper"', false)
        ->assertSee('data-controller="sidebar"', false)
        ->assertSee('data-state="expanded"', false)
        ->assertSee('data-sidebar-open-value="true"', false)
        ->assertSee('--sidebar-width: 16rem', false)
        ->assertSee('--sidebar-width-icon: 3rem', false)
        ->assertSee('data-slot="sidebar-inset"', false)
        ->assertDontSee('min-h-svh', false);
});

it('server-renders icon collapsible sidebars without offcanvas collapse', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::sidebar.provider :default-open="false">
            <x-hw::sidebar collapsible="icon">
                <x-hw::sidebar.menu>
                    <x-hw::sidebar.menu-item>
                        <x-hw::sidebar.menu-button href="/dashboard">
                            <x-hw::icon name="panel-left" />
                            <span>Dashboard</span>
                        </x-hw::sidebar.menu-button>
                    </x-hw::sidebar.menu-item>
                </x-hw::sidebar.menu>
            </x-hw::sidebar>
        </x-hw::sidebar.provider>
    BLADE);

    $view->assertSee('data-state="collapsed"', false)
        ->assertSee('data-collapsible="icon"', false)
        ->assertSee('data-sidebar-collapsible="icon"', false)
        ->assertSee('data-slot="icon"', false)
        ->assertSeeText('Dashboard');
});

it('can render the provider initially collapsed', function () {
    $view = $this->blade('<x-hw::sidebar.provider :default-open="false"><x-hw::sidebar /></x-hw::sidebar.provider>');

    $view->assertSee('data-state="collapsed"', false)
        ->assertSee('data-sidebar-open-value="false"', false)
        ->assertSee('data-collapsible="offcanvas"', false);
});

it('merges user stimulus attributes on the provider', function () {
    $view = $this->blade('<x-hw::sidebar.provider data-controller="analytics" data-action="sidebar:change->analytics#track" />');

    $view->assertSee('data-controller="sidebar analytics"', false)
        ->assertSee('keydown@window->sidebar#shortcut turbo:before-cache@window->sidebar#closeForCache sidebar:change->analytics#track', false);
});

it('renders sidebar side variant collapsible and inner structure', function () {
    $view = $this->blade('<x-hw::sidebar.provider><x-hw::sidebar side="right" variant="floating" collapsible="icon">Nav</x-hw::sidebar></x-hw::sidebar.provider>');

    $view->assertSee('data-slot="sidebar"', false)
        ->assertSee('data-side="right"', false)
        ->assertSee('data-variant="floating"', false)
        ->assertSee('data-collapsible=""', false)
        ->assertSee('data-action="click->sidebar#clickOutside"', false)
        ->assertSee('data-slot="sidebar-gap"', false)
        ->assertSee('data-slot="sidebar-container"', false)
        ->assertSee('data-slot="sidebar-inner"', false)
        ->assertSeeText('Nav');
});

it('renders a non-collapsible sidebar without gap and container wrappers', function () {
    $view = $this->blade('<x-hw::sidebar.provider><x-hw::sidebar collapsible="none">Nav</x-hw::sidebar></x-hw::sidebar.provider>');

    $view->assertSee('data-slot="sidebar"', false)
        ->assertSee('data-collapsible="none"', false)
        ->assertDontSee('data-slot="sidebar-gap"', false)
        ->assertDontSee('data-slot="sidebar-container"', false);
});

it('renders trigger and rail controls wired to the sidebar controller', function () {
    $view = $this->blade('<x-hw::sidebar.provider><x-hw::sidebar.trigger /><x-hw::sidebar><x-hw::sidebar.rail /></x-hw::sidebar></x-hw::sidebar.provider>');

    $view->assertSee('data-slot="sidebar-trigger"', false)
        ->assertSee('data-action="click-&gt;sidebar#toggle"', false)
        ->assertSee('aria-label="Toggle Sidebar"', false)
        ->assertSee('data-slot="sidebar-rail"', false)
        ->assertSee('tabindex="-1"', false)
        ->assertSee('data-slot="icon"', false);
});

it('renders structural sidebar sections', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::sidebar.provider>
            <x-hw::sidebar>
                <x-hw::sidebar.header>Header</x-hw::sidebar.header>
                <x-hw::sidebar.content>
                    <x-hw::sidebar.group>
                        <x-hw::sidebar.group-label>Projects</x-hw::sidebar.group-label>
                        <x-hw::sidebar.group-action aria-label="Add">+</x-hw::sidebar.group-action>
                        <x-hw::sidebar.group-content>Group content</x-hw::sidebar.group-content>
                    </x-hw::sidebar.group>
                </x-hw::sidebar.content>
                <x-hw::sidebar.separator />
                <x-hw::sidebar.footer>Footer</x-hw::sidebar.footer>
            </x-hw::sidebar>
        </x-hw::sidebar.provider>
    BLADE);

    $view->assertSee('data-slot="sidebar-header"', false)
        ->assertSee('data-slot="sidebar-content"', false)
        ->assertSee('data-slot="sidebar-group"', false)
        ->assertSee('data-slot="sidebar-group-label"', false)
        ->assertSee('data-slot="sidebar-group-action"', false)
        ->assertSee('data-slot="sidebar-group-content"', false)
        ->assertSee('data-slot="sidebar-separator"', false)
        ->assertSee('data-slot="sidebar-footer"', false)
        ->assertSee('role="separator"', false);
});

it('renders sidebar menu parts with active and size state', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::sidebar.provider>
            <x-hw::sidebar>
                <x-hw::sidebar.menu>
                    <x-hw::sidebar.menu-item>
                        <x-hw::sidebar.menu-button href="/dashboard" active size="lg">
                            Dashboard
                        </x-hw::sidebar.menu-button>
                        <x-hw::sidebar.menu-action show-on-hover aria-label="More">...</x-hw::sidebar.menu-action>
                        <x-hw::sidebar.menu-badge>12</x-hw::sidebar.menu-badge>
                        <x-hw::sidebar.menu-sub>
                            <x-hw::sidebar.menu-sub-item>
                                <x-hw::sidebar.menu-sub-button href="/dashboard/reports" active>Reports</x-hw::sidebar.menu-sub-button>
                            </x-hw::sidebar.menu-sub-item>
                        </x-hw::sidebar.menu-sub>
                    </x-hw::sidebar.menu-item>
                </x-hw::sidebar.menu>
            </x-hw::sidebar>
        </x-hw::sidebar.provider>
    BLADE);

    $view->assertSee('data-slot="sidebar-menu"', false)
        ->assertSee('data-slot="sidebar-menu-item"', false)
        ->assertSee('data-slot="sidebar-menu-button"', false)
        ->assertSee('href="/dashboard"', false)
        ->assertSee('data-size="lg"', false)
        ->assertSee('data-active="true"', false)
        ->assertSee('data-slot="sidebar-menu-action"', false)
        ->assertSee('data-show-on-hover="true"', false)
        ->assertSee('data-slot="sidebar-menu-badge"', false)
        ->assertSee('data-slot="sidebar-menu-sub"', false)
        ->assertSee('data-slot="sidebar-menu-sub-item"', false)
        ->assertSee('data-slot="sidebar-menu-sub-button"', false)
        ->assertSee('href="/dashboard/reports"', false);
});

it('renders sidebar input and skeleton helpers', function () {
    $view = $this->blade('<x-hw::sidebar.provider><x-hw::sidebar.input name="q" /><x-hw::sidebar.menu-skeleton show-icon /></x-hw::sidebar.provider>');

    $view->assertSee('data-slot="sidebar-input"', false)
        ->assertSee('name="q"', false)
        ->assertSee('data-slot="sidebar-menu-skeleton"', false)
        ->assertSee('data-slot="sidebar-menu-skeleton-icon"', false)
        ->assertSee('data-slot="sidebar-menu-skeleton-text"', false);
});

it('registers sidebar in the component catalog and subcomponent aliases', function () {
    $sidebar = HotwireRegistry::make()->component('sidebar');

    expect($sidebar->key)->toBe('sidebar')
        ->and($sidebar->controllers)->toBe(['sidebar'])
        ->and($sidebar->docs)->toBe('docs/components/sidebar.md');

    expect(ComponentAliases::subComponents())
        ->toHaveKey('sidebar.provider')
        ->toHaveKey('sidebar.trigger')
        ->toHaveKey('sidebar.menu-button');
});
