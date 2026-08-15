<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Illuminate\View\ViewException;

it('targets sidebar navigation links with frame', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::sidebar.brand href="/" frame="content">Brand</x-hw::sidebar.brand>
        <x-hw::sidebar.menu-button href="/tasks" frame="content">Tasks</x-hw::sidebar.menu-button>
        <x-hw::sidebar.menu-sub-button href="/tasks/open" frame="content">Open</x-hw::sidebar.menu-sub-button>
    BLADE);

    expect(substr_count((string) $view, 'data-turbo-frame="content"'))->toBe(3)
        ->and((string) $view)->not->toContain(' frame="content"');
});

it('rejects unsupported sidebar menu button types', function (string $component) {
    $this->blade("<x-hw::{$component} type=\"invalid\">Action</x-hw::{$component}>");
})->with(['sidebar.menu-button', 'sidebar.menu-sub-button'])
    ->throws(ViewException::class, 'Unsupported button type');

it('treats falsey but present sidebar destinations as links', function (string $component, string $href) {
    $view = $this->blade("<x-hw::{$component} :href=\"\$href\">Action</x-hw::{$component}>", ['href' => $href]);

    $view->assertSee('<a', false)
        ->assertSee('href="'.$href.'"', false)
        ->assertDontSee('<button', false)
        ->assertDontSee('<div', false);
})->with([
    ['sidebar.brand', ''],
    ['sidebar.brand', '0'],
    ['sidebar.menu-button', ''],
    ['sidebar.menu-button', '0'],
    ['sidebar.menu-sub-button', ''],
    ['sidebar.menu-sub-button', '0'],
]);

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

it('uses the persisted sidebar cookie when default open is omitted', function () {
    request()->cookies->set('sidebar_state', 'false');

    $view = $this->blade('<x-hw::sidebar.provider><x-hw::sidebar /></x-hw::sidebar.provider>');

    $view->assertSee('data-state="collapsed"', false)
        ->assertSee('data-sidebar-open-value="false"', false)
        ->assertSee('data-collapsible="offcanvas"', false);
});

it('uses the raw sidebar cookie header when Laravel cookie decryption drops the cookie', function () {
    request()->headers->set('Cookie', 'sidebar_state=false');

    $view = $this->blade('<x-hw::sidebar.provider><x-hw::sidebar /></x-hw::sidebar.provider>');

    $view->assertSee('data-state="collapsed"', false)
        ->assertSee('data-sidebar-open-value="false"', false)
        ->assertSee('data-collapsible="offcanvas"', false);
});

it('lets explicit default open override the persisted sidebar cookie', function () {
    request()->cookies->set('sidebar_state', 'false');

    $view = $this->blade('<x-hw::sidebar.provider :default-open="true"><x-hw::sidebar /></x-hw::sidebar.provider>');

    $view->assertSee('data-state="expanded"', false)
        ->assertSee('data-sidebar-open-value="true"', false)
        ->assertSee('data-collapsible=""', false);
});

it('merges user stimulus attributes on the provider', function () {
    $view = $this->blade('<x-hw::sidebar.provider data-controller="analytics" data-action="sidebar:change->analytics#track" />');

    $view->assertSee('data-controller="sidebar analytics"', false)
        ->assertSee('keydown@window->sidebar#shortcut turbo:before-cache@window->sidebar#closeForCache turbo:before-render@window->sidebar#preserveStateForRender sidebar:change->analytics#track', false);
});

it('renders sidebar side variant collapsible and inner structure', function () {
    $view = $this->blade('<x-hw::sidebar.provider><x-hw::sidebar side="right" variant="floating" collapsible="icon">Nav</x-hw::sidebar></x-hw::sidebar.provider>');

    $view->assertSee('data-slot="sidebar"', false)
        ->assertSee('data-side="right"', false)
        ->assertSee('data-variant="floating"', false)
        ->assertSee('data-collapsible=""', false)
        ->assertSee('data-mobile-state="closed"', false)
        ->assertSee('data-motion="default"', false)
        ->assertSee('data-action="click->sidebar#clickOutside"', false)
        ->assertSee('data-slot="sidebar-gap"', false)
        ->assertSee('data-slot="sidebar-container"', false)
        ->assertSee('data-slot="sidebar-inner"', false)
        ->assertSeeText('Nav');
});

it('normalizes mobile sidebar motion', function () {
    $none = $this->blade('<x-hw::sidebar.provider><x-hw::sidebar motion="none">Nav</x-hw::sidebar></x-hw::sidebar.provider>');
    $invalid = $this->blade('<x-hw::sidebar.provider><x-hw::sidebar motion="spin">Nav</x-hw::sidebar></x-hw::sidebar.provider>');

    $none->assertSee('data-motion="none"', false);
    $invalid->assertSee('data-motion="default"', false);
});

it('mounts Reveal directly on the collapsible sidebar container', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::sidebar.provider>
            <x-hw::sidebar
                reveal
                reveal-motion="flat"
                reveal-stagger="35ms"
                reveal-duration="380ms"
                reveal-delay="90ms"
                :reveal-max-steps="8"
                data-controller="analytics"
            >
                <x-hw::sidebar.menu-item data-reveal-item style="--reveal-index: 0">Dashboard</x-hw::sidebar.menu-item>
            </x-hw::sidebar>
        </x-hw::sidebar.provider>
        BLADE);

    $view->assertSee('data-slot="sidebar-container"', false)
        ->assertSee('data-controller="reveal analytics"', false)
        ->assertSee('data-reveal-trigger-value="load"', false)
        ->assertSee('data-reveal-scope="document"', false)
        ->assertSee('data-motion="flat"', false)
        ->assertSee('--reveal-stagger: 35ms', false)
        ->assertSee('--reveal-duration: 380ms', false)
        ->assertSee('--reveal-delay: 90ms', false)
        ->assertSee('--reveal-max-steps: 8', false)
        ->assertSee('data-reveal-item', false)
        ->assertDontSee('<div data-slot="reveal"', false);
});

it('omits Reveal wiring from the sidebar by default', function () {
    $view = $this->blade('<x-hw::sidebar.provider><x-hw::sidebar>Nav</x-hw::sidebar></x-hw::sidebar.provider>');

    $view->assertDontSee('data-reveal-scope', false)
        ->assertDontSee('data-controller="reveal', false);
});

it('mounts Reveal on the native non-collapsible sidebar surface', function () {
    $view = $this->blade('<x-hw::sidebar.provider><x-hw::sidebar collapsible="none" reveal>Nav</x-hw::sidebar></x-hw::sidebar.provider>');

    $view->assertSee('<aside', false)
        ->assertSee('data-slot="sidebar"', false)
        ->assertSee('data-controller="reveal"', false)
        ->assertSee('data-reveal-scope="document"', false)
        ->assertDontSee('data-slot="sidebar-container"', false);
});

it('rejects unsupported sidebar Reveal motion', function () {
    $this->blade('<x-hw::sidebar reveal reveal-motion="zoom">Nav</x-hw::sidebar>');
})->throws(ViewException::class, 'Supported values: rise, flat, fade.');

it('marks only the collapsible sidebar surface with the sidebar role marker', function () {
    $html = (string) $this->blade('<x-hw::sidebar.provider><x-hw::sidebar>Nav</x-hw::sidebar></x-hw::sidebar.provider>');

    expect(substr_count($html, 'data-sidebar="sidebar"'))->toBe(1)
        ->and($html)->toMatch('/data-slot="sidebar-inner"\s+data-sidebar="sidebar"/')
        ->and($html)->not->toMatch('/data-slot="sidebar"\s+data-sidebar="sidebar"/');
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

it('renders a sidebar brand with full and icon logos', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::sidebar.provider>
            <x-hw::sidebar>
                <x-hw::sidebar.header>
                    <x-hw::sidebar.brand href="/" label="Acme">
                        <span>Acme Cloud</span>

                        <x-slot:icon>
                            <span>AC</span>
                        </x-slot:icon>
                    </x-hw::sidebar.brand>
                </x-hw::sidebar.header>
            </x-hw::sidebar>
        </x-hw::sidebar.provider>
    BLADE);

    $view->assertSee('data-slot="sidebar-brand"', false)
        ->assertSee('data-sidebar="brand"', false)
        ->assertSee('href="/"', false)
        ->assertSee('aria-label="Acme"', false)
        ->assertSee('data-slot="sidebar-brand-logo"', false)
        ->assertSee('data-slot="sidebar-brand-icon"', false)
        ->assertSee('aria-hidden="true"', false)
        ->assertSeeText('Acme Cloud')
        ->assertSeeText('AC');
});

it('does not apply a link label to a non-link sidebar brand', function () {
    $view = $this->blade('<x-hw::sidebar.provider><x-hw::sidebar.brand label="Acme"><span>Acme Cloud</span></x-hw::sidebar.brand></x-hw::sidebar.provider>');

    $view->assertSee('data-slot="sidebar-brand"', false)
        ->assertDontSee('aria-label="Acme"', false)
        ->assertSee('data-slot="sidebar-brand-logo"', false)
        ->assertDontSee('data-slot="sidebar-brand-icon"', false)
        ->assertSeeText('Acme Cloud');
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
        ->and($sidebar->controllers)->toBe(['sidebar', 'reveal'])
        ->and($sidebar->docs)->toBe('docs/components/sidebar.md');

    expect(ComponentAliases::subComponents())
        ->toHaveKey('sidebar.provider')
        ->toHaveKey('sidebar.trigger')
        ->toHaveKey('sidebar.brand')
        ->toHaveKey('sidebar.menu-button');
});

it('lets the application configure its own reveal when the prop is off', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::sidebar data-controller="reveal" data-reveal-trigger-value="scroll" data-motion="flat">
            <div>Nav</div>
        </x-hw::sidebar>
        BLADE);

    // Filtering data-reveal-* belongs to the prop that mounts the internal controller. With it off
    // the visitor gets the controller they asked for and none of its configuration.
    $view->assertSee('data-reveal-trigger-value="scroll"', false)
        ->assertSee('data-motion="flat"', false);
});

it('keeps the internal reveal configuration when the prop is on', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::sidebar reveal data-reveal-trigger-value="scroll">
            <div>Nav</div>
        </x-hw::sidebar>
        BLADE);

    $view->assertSee('data-reveal-trigger-value="load"', false)
        ->assertDontSee('data-reveal-trigger-value="scroll"', false);
});
