<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Illuminate\Pagination\LengthAwarePaginator;

it('renders composed pagination subcomponents', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::pagination label="Search results pages" id="pages">
            <x-hw::pagination.content>
                <x-hw::pagination.item>
                    <x-hw::pagination.previous href="/users?page=1" />
                </x-hw::pagination.item>
                <x-hw::pagination.item>
                    <x-hw::pagination.link href="/users?page=1">1</x-hw::pagination.link>
                </x-hw::pagination.item>
                <x-hw::pagination.item>
                    <x-hw::pagination.link href="/users?page=2" active>2</x-hw::pagination.link>
                </x-hw::pagination.item>
                <x-hw::pagination.item>
                    <x-hw::pagination.ellipsis />
                </x-hw::pagination.item>
                <x-hw::pagination.item>
                    <x-hw::pagination.next href="/users?page=3" />
                </x-hw::pagination.item>
            </x-hw::pagination.content>
        </x-hw::pagination>
    BLADE);

    $view->assertSee('<nav', false)
        ->assertSee('role="navigation"', false)
        ->assertSee('aria-label="Search results pages"', false)
        ->assertSee('id="pages"', false)
        ->assertSee('<ul', false)
        ->assertSee('data-slot="pagination-content"', false)
        ->assertSee('<li', false)
        ->assertSee('data-slot="pagination-item"', false)
        ->assertSee('data-slot="pagination-previous"', false)
        ->assertSee('href="/users?page=1"', false)
        ->assertSee('data-slot="pagination-link"', false)
        ->assertSee('<span', false)
        ->assertSee('aria-current="page"', false)
        ->assertSee('data-active="true"', false)
        ->assertSee('data-slot="pagination-ellipsis"', false)
        ->assertSee('aria-label="More pages"', false)
        ->assertSee('data-slot="pagination-next"', false)
        ->assertSee('href="/users?page=3"', false)
        ->assertSeeText('Previous')
        ->assertSeeText('Next')
        ->assertDontSee('mx-auto', false);
});

it('renders links from a length-aware paginator', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" turbo-frame="users" />', [
        'paginator' => $paginator,
    ]);

    $html = (string) $view;

    expect($html)->toContain('data-slot="pagination"')
        ->toContain('data-slot="pagination-previous"')
        ->toContain('href="/users?page=9"')
        ->toContain('data-slot="pagination-next"')
        ->toContain('href="/users?page=11"')
        ->toContain('data-turbo-frame="users"')
        ->toContain('data-slot="pagination-ellipsis"')
        ->toContain('aria-current="page"')
        ->toContain('data-active="true"')
        ->not->toContain('href="/users?page=10"');
});

it('does not render automatic pagination when the paginator has no extra pages', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 10, 10, 1, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" />', [
        'paginator' => $paginator,
    ]);

    $view->assertDontSee('<nav', false)
        ->assertDontSee('data-slot="pagination"', false);
});

it('renders disabled previous and next controls as spans without href', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::pagination>
            <x-hw::pagination.content>
                <x-hw::pagination.item>
                    <x-hw::pagination.previous disabled />
                </x-hw::pagination.item>
                <x-hw::pagination.item>
                    <x-hw::pagination.next disabled />
                </x-hw::pagination.item>
            </x-hw::pagination.content>
        </x-hw::pagination>
    BLADE);

    $view->assertSee('data-slot="pagination-previous"', false)
        ->assertSee('data-slot="pagination-next"', false)
        ->assertSee('data-disabled="true"', false)
        ->assertSee('aria-disabled="true"', false)
        ->assertDontSee('href=', false);
});

it('registers pagination in the component catalog and subcomponent aliases', function () {
    $pagination = HotwireRegistry::make()->component('pagination');

    expect($pagination->key)->toBe('pagination')
        ->and($pagination->controllers)->toBe([])
        ->and($pagination->docs)->toBe('docs/components/pagination.md');

    expect(ComponentAliases::subComponents())
        ->toHaveKey('pagination.content')
        ->toHaveKey('pagination.item')
        ->toHaveKey('pagination.link')
        ->toHaveKey('pagination.previous')
        ->toHaveKey('pagination.next')
        ->toHaveKey('pagination.ellipsis');
});
