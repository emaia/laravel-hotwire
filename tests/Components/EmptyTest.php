<?php

it('renders an empty root with semantic slot', function () {
    $view = $this->blade('<x-hwc::empty>No results</x-hwc::empty>');

    $view->assertSee('data-slot="empty"', false)
        ->assertSeeText('No results')
        ->assertDontSee('rounded-xl', false);
});

it('renders empty subcomponents with semantic slots', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hwc::empty>
            <x-hwc::empty.header>
                <x-hwc::empty.media variant="icon"><x-hwc::icon name="search" /></x-hwc::empty.media>
                <x-hwc::empty.title>No projects</x-hwc::empty.title>
                <x-hwc::empty.description>Create your first project to get started.</x-hwc::empty.description>
            </x-hwc::empty.header>
            <x-hwc::empty.content><x-hwc::button>Create project</x-hwc::button></x-hwc::empty.content>
        </x-hwc::empty>
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
    $view = $this->blade('<x-hwc::empty id="state" class="min-h-64" data-test="empty">Empty</x-hwc::empty>');

    $view->assertSee('id="state"', false)
        ->assertSee('class="min-h-64"', false)
        ->assertSee('data-test="empty"', false);
});
