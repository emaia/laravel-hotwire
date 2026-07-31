<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;

it('targets generated and composed breadcrumb links with frame', function () {
    $items = [
        ['label' => 'Projects', 'href' => '/projects'],
        ['label' => 'Archived', 'href' => '/projects/archived', 'frame' => false],
        ['label' => 'Current'],
    ];

    $generated = $this->blade('<x-hw::breadcrumb :items="$items" frame="content" />', ['items' => $items]);
    $composed = $this->blade('<x-hw::breadcrumb><x-hw::breadcrumb.link href="/projects" frame="content">Projects</x-hw::breadcrumb.link></x-hw::breadcrumb>');

    expect(substr_count((string) $generated, 'data-turbo-frame="content"'))->toBe(1)
        ->and((string) $generated)->not->toContain(' frame=')
        ->and((string) $composed)->toContain('data-turbo-frame="content"');
});

it('omits frame metadata from href-less composed links', function () {
    $view = $this->blade('<x-hw::breadcrumb.link frame="content">Current</x-hw::breadcrumb.link>');

    $view->assertDontSee('data-turbo-frame', false);
});

it('renders a breadcrumb root with semantic markup', function () {
    $view = $this->blade('<x-hw::breadcrumb id="trail">Trail</x-hw::breadcrumb>');

    $view->assertSee('<nav', false)
        ->assertSee('id="trail"', false)
        ->assertSee('data-slot="breadcrumb"', false)
        ->assertSee('aria-label="Breadcrumb"', false)
        ->assertSeeText('Trail')
        ->assertDontSee('text-muted-foreground', false);
});

it('renders breadcrumb subcomponents with semantic slots', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::breadcrumb label="Project path">
            <x-hw::breadcrumb.list>
                <x-hw::breadcrumb.item>
                    <x-hw::breadcrumb.link href="/dashboard">Dashboard</x-hw::breadcrumb.link>
                </x-hw::breadcrumb.item>
                <x-hw::breadcrumb.separator />
                <x-hw::breadcrumb.item>
                    <x-hw::breadcrumb.link href="/projects">Projects</x-hw::breadcrumb.link>
                </x-hw::breadcrumb.item>
                <x-hw::breadcrumb.separator>
                    <span>/</span>
                </x-hw::breadcrumb.separator>
                <x-hw::breadcrumb.item>
                    <x-hw::breadcrumb.page>Laravel Hotwire</x-hw::breadcrumb.page>
                </x-hw::breadcrumb.item>
            </x-hw::breadcrumb.list>
        </x-hw::breadcrumb>
    BLADE);

    $view->assertSee('aria-label="Project path"', false)
        ->assertSee('<ol', false)
        ->assertSee('data-slot="breadcrumb-list"', false)
        ->assertSee('<li', false)
        ->assertSee('data-slot="breadcrumb-item"', false)
        ->assertSee('<a data-slot="breadcrumb-link" href="/dashboard"', false)
        ->assertSee('data-slot="breadcrumb-separator"', false)
        ->assertSee('aria-hidden="true"', false)
        ->assertSee('<span data-slot="breadcrumb-page" aria-current="page"', false)
        ->assertSeeText('Dashboard')
        ->assertSeeText('Projects')
        ->assertSeeText('Laravel Hotwire');
});

it('renders breadcrumb items from the items prop', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::breadcrumb :items="[
            ['label' => 'Dashboard', 'href' => '/dashboard'],
            ['label' => 'Projects', 'href' => '/projects'],
            ['label' => 'Laravel Hotwire'],
        ]" />
    BLADE);

    $html = (string) $view;

    expect(substr_count($html, 'data-slot="breadcrumb-item"'))->toBe(3);
    expect(substr_count($html, 'data-slot="breadcrumb-separator"'))->toBe(2);

    $view->assertSee('<ol', false)
        ->assertSee('href="/dashboard"', false)
        ->assertSee('href="/projects"', false)
        ->assertSee('<span data-slot="breadcrumb-page" aria-current="page"', false)
        ->assertSeeText('Laravel Hotwire');
});

it('supports explicit current items and ellipsis items', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::breadcrumb :items="[
            ['label' => 'Dashboard', 'href' => '/dashboard'],
            ['type' => 'ellipsis', 'label' => 'More pages'],
            ['label' => 'Projects', 'href' => '/projects', 'current' => true],
        ]" />
    BLADE);

    $view->assertSee('data-slot="breadcrumb-ellipsis"', false)
        ->assertSee('aria-label="More pages"', false)
        ->assertSee('aria-hidden="true"', false)
        ->assertSee('<svg', false)
        ->assertSee('Projects', false)
        ->assertSee('<span data-slot="breadcrumb-page" aria-current="page"', false)
        ->assertDontSee('href="/projects"', false);
});

it('renders an ellipsis subcomponent with an accessible label', function () {
    $view = $this->blade('<x-hw::breadcrumb.ellipsis label="More sections" data-test="crumbs" />');

    $view->assertSee('data-slot="breadcrumb-ellipsis"', false)
        ->assertSee('aria-label="More sections"', false)
        ->assertSee('data-test="crumbs"', false)
        ->assertSee('<svg', false)
        ->assertDontSee('&hellip;', false);
});

it('passes through attributes on subcomponents', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::breadcrumb>
            <x-hw::breadcrumb.list id="crumbs-list">
                <x-hw::breadcrumb.item data-test="item">
                    <x-hw::breadcrumb.link href="/dashboard" rel="home">Dashboard</x-hw::breadcrumb.link>
                </x-hw::breadcrumb.item>
                <x-hw::breadcrumb.separator data-test="separator" />
                <x-hw::breadcrumb.item>
                    <x-hw::breadcrumb.page title="Current page">Current</x-hw::breadcrumb.page>
                </x-hw::breadcrumb.item>
            </x-hw::breadcrumb.list>
        </x-hw::breadcrumb>
    BLADE);

    $view->assertSee('id="crumbs-list"', false)
        ->assertSee('data-test="item"', false)
        ->assertSee('rel="home"', false)
        ->assertSee('data-test="separator"', false)
        ->assertSee('title="Current page"', false);
});

it('registers breadcrumb in the component catalog and subcomponent aliases', function () {
    $breadcrumb = HotwireRegistry::make()->component('breadcrumb');

    expect($breadcrumb->key)->toBe('breadcrumb')
        ->and($breadcrumb->controllers)->toBe([])
        ->and($breadcrumb->docs)->toBe('docs/components/breadcrumb.md');

    expect(ComponentAliases::subComponents())
        ->toHaveKey('breadcrumb.list')
        ->toHaveKey('breadcrumb.item')
        ->toHaveKey('breadcrumb.link')
        ->toHaveKey('breadcrumb.page')
        ->toHaveKey('breadcrumb.separator')
        ->toHaveKey('breadcrumb.ellipsis');
});
