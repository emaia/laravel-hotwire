<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Illuminate\View\ViewException;

it('renders a composable side panel with server state and accessibility hooks', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::side-panel name="project-nav" id="project-layout" panel-id="project-nav-region" width="18rem" side="right">
            <x-hw::side-panel.panel id="ignored-panel-id">Navigation</x-hw::side-panel.panel>
            <x-hw::side-panel.inset data-slot="wrong-inset">
                <x-hw::side-panel.trigger />
                Main
            </x-hw::side-panel.inset>
        </x-hw::side-panel>
    BLADE);

    $view->assertSee('id="project-layout"', false)
        ->assertSee('data-slot="side-panel"', false)
        ->assertSee('data-controller="side-panel"', false)
        ->assertSee('data-state="expanded"', false)
        ->assertSee('data-side="right"', false)
        ->assertSee('data-side-panel-name-value="project-nav"', false)
        ->assertSee('data-side-panel-open-value="true"', false)
        ->assertSee('data-side-panel-cookie-name-value="side_panel_project-nav_state"', false)
        ->assertSee('turbo:before-render@window->side-panel#preserveStateForRender', false)
        ->assertSee('--side-panel-width: 18rem', false)
        ->assertSee('data-slot="side-panel-panel"', false)
        ->assertSee('data-side-panel-target="panel"', false)
        ->assertSee('data-slot="side-panel-inset"', false)
        ->assertDontSee('wrong-inset', false)
        ->assertSee('data-slot="side-panel-trigger"', false)
        ->assertSee('data-side-panel-target="trigger"', false)
        ->assertSee('id="project-nav-region"', false)
        ->assertSee('aria-controls="project-nav-region"', false)
        ->assertDontSee('ignored-panel-id', false)
        ->assertSee('aria-expanded="true"', false)
        ->assertDontSee(' inert', false);
});

it('server-renders the persisted collapsed state without exposing panel focus', function () {
    request()->headers->set('Cookie', 'side_panel_project-nav_state=false');

    $view = $this->blade(<<<'BLADE'
        <x-hw::side-panel name="project-nav">
            <x-hw::side-panel.panel id="project-nav-panel"><a href="/tasks">Tasks</a></x-hw::side-panel.panel>
            <x-hw::side-panel.trigger />
        </x-hw::side-panel>
    BLADE);

    $view->assertSee('data-state="collapsed"', false)
        ->assertSee('data-side-panel-open-value="false"', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertSee(' inert', false);
});

it('lets an explicit default override the persisted side panel cookie', function () {
    request()->cookies->set('side_panel_project-nav_state', 'false');

    $view = $this->blade('<x-hw::side-panel name="project-nav" :default-open="true"><x-hw::side-panel.panel /></x-hw::side-panel>');

    $view->assertSee('data-state="expanded"', false)
        ->assertSee('data-side-panel-open-value="true"', false)
        ->assertDontSee(' inert', false);
});

it('ignores persisted state when side panel persistence is disabled', function () {
    request()->headers->set('Cookie', 'side_panel_project-nav_state=false');

    $view = $this->blade('<x-hw::side-panel name="project-nav" :persist="false"><x-hw::side-panel.panel /></x-hw::side-panel>');

    $view->assertSee('data-state="expanded"', false)
        ->assertSee('data-side-panel-open-value="true"', false)
        ->assertSee('data-side-panel-persist-value="false"', false);
});

it('rejects unsupported side panel sides', function () {
    $this->blade('<x-hw::side-panel name="project-nav" side="top" />');
})->throws(ViewException::class, 'Side Panel side must be one of: left, right. Got: top');

it('merges custom stimulus wiring without allowing side panel state overrides', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::side-panel
            name="filters"
            controller="workspace-panel"
            data-controller="analytics"
            data-action="workspace-panel:change->analytics#track"
            data-workspace-panel-open-value="false"
            data-sidebar-target="workspace"
            data-sidecar-value="kept"
            data-state="collapsed"
            data-side="right"
            style="min-height: 20rem"
        >
            <x-hw::side-panel.panel data-workspace-panel-target="wrong" inert />
            <x-hw::side-panel.trigger
                data-workspace-panel-target="wrong"
                data-action="click->analytics#track"
                aria-controls="wrong"
                aria-expanded="false"
            />
        </x-hw::side-panel>
    BLADE);

    $view->assertSee('data-controller="workspace-panel analytics"', false)
        ->assertSee('workspace-panel:change->analytics#track', false)
        ->assertSee('data-workspace-panel-open-value="true"', false)
        ->assertSee('data-sidebar-target="workspace"', false)
        ->assertSee('data-sidecar-value="kept"', false)
        ->assertSee('data-state="expanded"', false)
        ->assertSee('data-side="left"', false)
        ->assertSee('style="--side-panel-width: 16rem; min-height: 20rem"', false)
        ->assertSee('data-workspace-panel-target="panel"', false)
        ->assertSee('data-workspace-panel-target="trigger"', false)
        ->assertDontSee('data-workspace-panel-target="wrong"', false)
        ->assertDontSee('aria-controls="wrong"', false)
        ->assertSee('aria-expanded="true"', false)
        ->assertDontSee(' inert', false)
        ->assertSee('click->workspace-panel#toggle click->analytics#track', false);
});

it('registers side panel in the component catalog and aliases', function () {
    $component = HotwireRegistry::make()->component('side-panel');

    expect($component->controllers)->toBe(['side-panel'])
        ->and($component->docs)->toBe('docs/components/side-panel.md')
        ->and($component->styling->structuralSlots())->toContain('side-panel-panel');

    expect(ComponentAliases::subComponents())
        ->toHaveKey('side-panel.panel')
        ->toHaveKey('side-panel.trigger')
        ->toHaveKey('side-panel.inset');
});
