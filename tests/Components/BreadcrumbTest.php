<?php

use Emaia\LaravelHotwire\Components\Breadcrumb;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\View\ViewException;

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

// --- Items descriptor guard ---

it('rejects invalid generated breadcrumb item descriptors', function (array $items, string $message) {
    expect(fn () => new Breadcrumb(items: $items))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'missing label' => [
        [['href' => '/dashboard']],
        'must define [label]',
    ],
    'invalid label' => [
        [['label' => null]],
        '[label] must be a string, integer, Stringable or Htmlable',
    ],
    'invalid href' => [
        [['label' => 'Dashboard', 'href' => 7]],
        '[href] must be a string, Stringable or null',
    ],
    'invalid current state' => [
        [['label' => 'Dashboard', 'current' => 'yes']],
        '[current] must be a boolean',
    ],
    'unknown type' => [
        [['label' => 'More', 'type' => 'elipsis']],
        '[type] must be item or ellipsis',
    ],
    'more than one current page' => [
        [
            ['label' => 'Dashboard'],
            ['label' => 'Projects', 'href' => '/projects'],
            ['label' => 'Current'],
        ],
        'resolve more than one current page',
    ],
]);

it('accepts integer, Stringable and Htmlable breadcrumb item descriptors', function () {
    $items = [
        ['label' => Str::of('dashboard')->title(), 'href' => Str::of('/dashboard')],
        ['label' => 7, 'href' => '/7'],
        ['label' => new HtmlString('<strong>Current</strong>')],
    ];

    $html = (string) $this->blade('<x-hw::breadcrumb :items="$items" />', ['items' => $items]);

    expect($html)->toContain('href="/dashboard"')
        ->and($html)->toContain('Dashboard')
        ->and($html)->toContain('href="/7"')
        ->and($html)->toContain('<strong>Current</strong>')
        ->and(substr_count($html, 'aria-current="page"'))->toBe(1);
});

it('keeps an ellipsis item label optional', function () {
    $items = [
        ['label' => 'Dashboard', 'href' => '/dashboard'],
        ['type' => 'ellipsis'],
        ['label' => 'Current'],
    ];

    $this->blade('<x-hw::breadcrumb :items="$items" ellipsis-label="More sections" />', ['items' => $items])
        ->assertSee('data-slot="breadcrumb-ellipsis"', false)
        ->assertSee('aria-label="More sections"', false);
});

// --- Composition boundary ---

it('rejects combining generated items with slot composition', function () {
    $items = [['label' => 'Current']];

    expect(fn () => (string) $this->blade(<<<'BLADE'
        <x-hw::breadcrumb :items="$items">
            <x-hw::breadcrumb.list>
                <x-hw::breadcrumb.item>Manual</x-hw::breadcrumb.item>
            </x-hw::breadcrumb.list>
        </x-hw::breadcrumb>
    BLADE, ['items' => $items]))
        ->toThrow(ViewException::class, 'cannot combine [items] with slot composition');
});

it('renders generated items through the breadcrumb subcomponents', function () {
    $items = [
        ['label' => 'Dashboard', 'href' => '/dashboard', 'frame' => 'content'],
        ['type' => 'ellipsis'],
        ['label' => 'Current'],
    ];

    $generated = (string) $this->blade('<x-hw::breadcrumb :items="$items" />', ['items' => $items]);

    $composed = (string) $this->blade(<<<'BLADE'
        <x-hw::breadcrumb>
            <x-hw::breadcrumb.list>
                <x-hw::breadcrumb.item>
                    <x-hw::breadcrumb.link href="/dashboard" frame="content">Dashboard</x-hw::breadcrumb.link>
                </x-hw::breadcrumb.item>
                <x-hw::breadcrumb.separator />
                <x-hw::breadcrumb.item>
                    <x-hw::breadcrumb.ellipsis label="More pages" />
                </x-hw::breadcrumb.item>
                <x-hw::breadcrumb.separator />
                <x-hw::breadcrumb.item>
                    <x-hw::breadcrumb.page>Current</x-hw::breadcrumb.page>
                </x-hw::breadcrumb.item>
            </x-hw::breadcrumb.list>
        </x-hw::breadcrumb>
    BLADE);

    $normalize = fn (string $html) => trim(preg_replace('/\s+/', ' ', preg_replace('/>\s+</', '><', $html)));

    expect($normalize($generated))->toBe($normalize($composed));
});

it('composes a dropdown of collapsed pages inside a breadcrumb item', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::breadcrumb>
            <x-hw::breadcrumb.list>
                <x-hw::breadcrumb.item>
                    <x-hw::breadcrumb.link href="/dashboard">Dashboard</x-hw::breadcrumb.link>
                </x-hw::breadcrumb.item>
                <x-hw::breadcrumb.separator />
                <x-hw::breadcrumb.item>
                    <x-hw::dropdown>
                        <x-hw::dropdown.trigger aria-label="Show collapsed pages">
                            <x-hw::breadcrumb.ellipsis label="More pages" />
                        </x-hw::dropdown.trigger>
                        <x-hw::dropdown.content>
                            <x-hw::dropdown.item href="/projects">Projects</x-hw::dropdown.item>
                        </x-hw::dropdown.content>
                    </x-hw::dropdown>
                </x-hw::breadcrumb.item>
                <x-hw::breadcrumb.separator />
                <x-hw::breadcrumb.item>
                    <x-hw::breadcrumb.page>Laravel Hotwire</x-hw::breadcrumb.page>
                </x-hw::breadcrumb.item>
            </x-hw::breadcrumb.list>
        </x-hw::breadcrumb>
    BLADE);

    expect($html)->toContain('data-controller="dropdown"')
        ->and($html)->toContain('data-slot="dropdown-trigger"')
        ->and($html)->toContain('aria-label="Show collapsed pages"')
        ->and($html)->toContain('data-slot="breadcrumb-ellipsis"')
        ->and($html)->toContain('href="/projects"')
        ->and(substr_count($html, 'aria-current="page"'))->toBe(1);
});
