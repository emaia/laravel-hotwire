<?php

it('renders an empty root with semantic slot', function () {
    $view = $this->blade('<x-hw::empty-state>No results</x-hw::empty-state>');

    $view->assertSee('data-slot="empty-state"', false)
        ->assertSeeText('No results')
        ->assertDontSee('rounded-xl', false);
});

it('renders an empty root with the short tag syntax', function () {
    $view = $this->blade('<hw:empty-state>No results</hw:empty-state>');

    $view->assertSee('data-slot="empty-state"', false)
        ->assertSeeText('No results');
});

it('renders empty subcomponents with semantic slots', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::empty-state>
            <x-hw::empty-state.header>
                <x-hw::empty-state.media variant="icon"><x-hw::icon name="search" /></x-hw::empty-state.media>
                <x-hw::empty-state.title>No projects</x-hw::empty-state.title>
                <x-hw::empty-state.description>Create your first project to get started.</x-hw::empty-state.description>
            </x-hw::empty-state.header>
            <x-hw::empty-state.content><x-hw::button>Create project</x-hw::button></x-hw::empty-state.content>
        </x-hw::empty-state>
    BLADE);

    $view->assertSee('data-slot="empty-state-header"', false)
        ->assertSee('data-slot="empty-state-media"', false)
        ->assertSee('data-variant="icon"', false)
        ->assertSee('data-slot="empty-state-title"', false)
        ->assertSee('data-slot="empty-state-description"', false)
        ->assertSee('data-slot="empty-state-content"', false)
        ->assertSeeText('No projects')
        ->assertSeeText('Create project');
});

it('passes through attributes', function () {
    $view = $this->blade('<x-hw::empty-state id="state" class="min-h-64" data-test="empty">Empty</x-hw::empty-state>');

    $view->assertSee('id="state"', false)
        ->assertSee('class="min-h-64"', false)
        ->assertSee('data-test="empty"', false);
});
