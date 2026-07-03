<?php

it('renders an empty root with semantic slot', function () {
    $view = $this->blade('<x-hw::empty>No results</x-hw::empty>');

    $view->assertSee('data-slot="empty"', false)
        ->assertSeeText('No results')
        ->assertDontSee('rounded-xl', false);
});

it('renders empty subcomponents with semantic slots', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::empty>
            <x-hw::empty.header>
                <x-hw::empty.media variant="icon"><x-hw::icon name="search" /></x-hw::empty.media>
                <x-hw::empty.title>No projects</x-hw::empty.title>
                <x-hw::empty.description>Create your first project to get started.</x-hw::empty.description>
            </x-hw::empty.header>
            <x-hw::empty.content><x-hw::button>Create project</x-hw::button></x-hw::empty.content>
        </x-hw::empty>
    BLADE);

    $view->assertSee('data-slot="empty-header"', false)
        ->assertSee('data-slot="empty-icon"', false)
        ->assertSee('data-variant="icon"', false)
        ->assertSee('data-slot="empty-title"', false)
        ->assertSee('data-slot="empty-description"', false)
        ->assertSee('data-slot="empty-content"', false)
        ->assertSeeText('No projects')
        ->assertSeeText('Create project');
});

it('passes through attributes', function () {
    $view = $this->blade('<x-hw::empty id="state" class="min-h-64" data-test="empty">Empty</x-hw::empty>');

    $view->assertSee('id="state"', false)
        ->assertSee('class="min-h-64"', false)
        ->assertSee('data-test="empty"', false);
});
