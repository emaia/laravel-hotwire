<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

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

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" frame="users" />', [
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

it('uses only frame as the generated paginator target prop', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" frame=" users " />', [
        'paginator' => $paginator,
    ]);

    expect((string) $view)
        ->toContain('data-turbo-frame="users"')
        ->not->toContain(' frame=')
        ->not->toContain(' turbo-frame=');
});

it('omits frame metadata from inactive paginator controls', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::pagination>
            <x-hw::pagination.content>
                <x-hw::pagination.item><x-hw::pagination.link active frame="results">1</x-hw::pagination.link></x-hw::pagination.item>
                <x-hw::pagination.item><x-hw::pagination.previous disabled frame="results" /></x-hw::pagination.item>
                <x-hw::pagination.item><x-hw::pagination.next :frame="false" href="/users?page=2" /></x-hw::pagination.item>
            </x-hw::pagination.content>
        </x-hw::pagination>
    BLADE);

    $view->assertDontSee('data-turbo-frame', false);
});

it('adds turbo stream to generated paginator links', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" turbo-stream />', [
        'paginator' => $paginator,
    ]);

    $html = (string) $view;

    expect($html)
        ->toContain('data-turbo-stream')
        ->not->toContain(' turbo-stream');

    preg_match('/<span[^>]*aria-current="page"[^>]*>/', $html, $currentPage);

    expect($currentPage[0] ?? '')->not->toContain('data-turbo-stream');
});

it('wires generated pagination for incremental loading', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade(<<<'BLADE'
        <x-hw::pagination
            :paginator="$paginator"
            incremental
            load-more-label="Load more users"
            loading-label="Loading users"
            loaded-label="Users loaded"
            error-label="Users failed to load"
            append-to="#users-list"
            scroll-to="#users"
        />
    BLADE, ['paginator' => $paginator]);

    $html = (string) $view;

    expect($html)
        ->toContain('data-controller="pagination"')
        ->toContain('data-pagination-loading-label-value="Loading users"')
        ->toContain('data-pagination-loaded-label-value="Users loaded"')
        ->toContain('data-pagination-error-label-value="Users failed to load"')
        ->toContain('data-pagination-append-to-value="#users-list"')
        ->toContain('data-pagination-scroll-to-value="#users"')
        ->toContain('data-slot="pagination-status"')
        ->toContain('data-pagination-target="status"')
        ->toContain('role="status"')
        ->toContain('aria-live="polite"')
        ->toContain('data-pagination-target="next"')
        ->toContain('data-action="click-&gt;pagination#load"')
        ->toContain('aria-label="Load more users"')
        ->toContain('Load more users')
        ->toContain('data-slot="pagination-next-content"')
        ->toContain('data-slot="pagination-next-loading-content"')
        ->toContain('data-slot="pagination-next-loading-label"')
        ->toContain('Loading users')
        ->toContain('data-slot="pagination-next-spinner"')
        ->toContain('aria-hidden="true"')
        ->toContain('data-slot="pagination-next-icon"')
        ->toContain('d="m6 9 6 6 6-6"')
        ->not->toContain('d="m9 18 6-6-6-6"')
        ->not->toContain('data-pagination-infinite-value="true"')
        ->not->toContain('<nav aria-live')
        ->not->toContain('Previous');
});

it('allows incremental pagination to customize the load more icon slot', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade(<<<'BLADE'
        <x-hw::pagination :paginator="$paginator" incremental append-to="#users-list">
            <x-slot:loadMoreIcon>
                <span data-testid="load-more-icon">v</span>
            </x-slot:loadMoreIcon>
        </x-hw::pagination>
    BLADE, ['paginator' => $paginator]);

    expect((string) $view)
        ->toContain('data-testid="load-more-icon"')
        ->not->toContain('data-slot="pagination-next-icon"')
        ->not->toContain('d="m6 9 6 6 6-6"');
});

it('emits controller defaults for incremental pagination', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" incremental append-to="#users-list" />', [
        'paginator' => $paginator,
    ]);

    expect((string) $view)
        ->toContain('data-pagination-loading-label-value="Loading more"')
        ->toContain('data-pagination-loaded-label-value="More results loaded"')
        ->toContain('data-pagination-error-label-value="Loading failed"')
        ->toContain('data-pagination-root-margin-value="300px"')
        ->toContain('data-pagination-threshold-value="1"');
});

it('renders only the spinner when incremental loading label is disabled', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" incremental append-to="#users-list" loading-label="" />', [
        'paginator' => $paginator,
    ]);

    expect((string) $view)
        ->toContain('data-pagination-loading-label-value=""')
        ->toContain('data-slot="pagination-next-loading-content"')
        ->toContain('data-slot="pagination-next-spinner"')
        ->not->toContain('data-slot="pagination-next-loading-label"');
});

it('renders only the spinner when incremental loading label is false', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" incremental append-to="#users-list" :loading-label="false" />', [
        'paginator' => $paginator,
    ]);

    expect((string) $view)
        ->toContain('data-pagination-loading-label-value=""')
        ->toContain('data-slot="pagination-next-loading-content"')
        ->toContain('data-slot="pagination-next-spinner"')
        ->not->toContain('data-slot="pagination-next-loading-label"');
});

it('uses the default loading label when the loading label attribute is boolean true', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" incremental append-to="#users-list" loading-label />', [
        'paginator' => $paginator,
    ]);

    expect((string) $view)
        ->toContain('data-slot="pagination-next-loading-label"')
        ->toContain('Loading more')
        ->toContain('data-pagination-loading-label-value="Loading more"')
        ->not->toContain('data-pagination-loading-label-value="1"')
        ->not->toContain('>1<');
});

it('keeps icon incremental pagination icon sized while loading', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" incremental append-to="#users-list" display="icons" />', [
        'paginator' => $paginator,
    ]);

    expect((string) $view)
        ->toContain('data-size="icon"')
        ->toContain('data-slot="pagination-next-loading-content"')
        ->toContain('data-slot="pagination-next-spinner"')
        ->not->toContain('data-slot="pagination-next-label"')
        ->not->toContain('data-slot="pagination-next-loading-label"');
});

it('falls back to the next aria label when the incremental load more label is empty', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" incremental append-to="#users-list" load-more-label="" next-aria-label="Load next users page" />', [
        'paginator' => $paginator,
    ]);

    $html = (string) $view;

    preg_match('/<a[^>]*data-slot="pagination-next"[^>]*>/', $html, $nextControl);

    expect($html)
        ->toContain('aria-label="Load next users page"')
        ->and($nextControl[0] ?? '')->not->toContain('aria-label=""');
});

it('wires generated pagination for infinite incremental loading', function () {
    $paginator = new CursorPaginator([
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
    ], 2, null, ['path' => '/users', 'parameters' => ['id']]);

    $view = $this->blade(<<<'BLADE'
        <x-hw::pagination
            :paginator="$paginator"
            infinite
            root-margin="200px"
            :threshold="0.5"
        />
    BLADE, ['paginator' => $paginator]);

    $html = (string) $view;

    expect($html)
        ->toContain('data-controller="pagination"')
        ->toContain('data-pagination-infinite-value="true"')
        ->toContain('data-pagination-root-margin-value="200px"')
        ->toContain('data-pagination-threshold-value="0.5"')
        ->toContain('data-slot="pagination-next-content"')
        ->toContain('data-slot="pagination-next-loading-content"')
        ->toContain('data-slot="pagination-next-loading-label"')
        ->toContain('data-slot="pagination-next-spinner"')
        ->toContain('data-pagination-target="next"')
        ->toContain('data-action="click-&gt;pagination#load"')
        ->not->toContain('data-slot="pagination-previous"');
});

it('merges stimulus attributes when incremental loading is enabled', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" incremental append-to="#users-list" :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'pagination:error\')" />', [
        'paginator' => $paginator,
    ]);

    expect((string) $view)
        ->toContain('data-controller="pagination analytics"')
        ->toContain('data-action="pagination:error->analytics#track"')
        ->toContain('data-pagination-append-to-value="#users-list"');
});

it('does not render incremental pagination when there is no next page', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 20, 10, 2, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" incremental />', [
        'paginator' => $paginator,
    ]);

    $view->assertDontSee('<nav', false)
        ->assertDontSee('data-controller="pagination"', false);
});

it('adds turbo stream to manual pagination links and controls', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::pagination>
            <x-hw::pagination.content>
                <x-hw::pagination.item>
                    <x-hw::pagination.previous href="/users?page=1" turbo-stream />
                </x-hw::pagination.item>
                <x-hw::pagination.item>
                    <x-hw::pagination.link href="/users?page=2" turbo-stream>2</x-hw::pagination.link>
                </x-hw::pagination.item>
                <x-hw::pagination.item>
                    <x-hw::pagination.link active turbo-stream>3</x-hw::pagination.link>
                </x-hw::pagination.item>
                <x-hw::pagination.item>
                    <x-hw::pagination.next disabled turbo-stream />
                </x-hw::pagination.item>
            </x-hw::pagination.content>
        </x-hw::pagination>
    BLADE);

    $html = (string) $view;

    expect(substr_count($html, 'data-turbo-stream='))->toBe(2);

    preg_match('/<span[^>]*aria-current="page"[^>]*>/', $html, $currentPage);
    preg_match('/<span[^>]*data-slot="pagination-next"[^>]*>/', $html, $disabledNext);

    expect($currentPage[0] ?? '')->not->toContain('data-turbo-stream')
        ->and($disabledNext[0] ?? '')->not->toContain('data-turbo-stream');
});

it('allows previous and next aria labels to be customized', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade(
        '<x-hw::pagination :paginator="$paginator" display="icons" previous-aria-label="Previous results page" next-aria-label="Next results page" />',
        ['paginator' => $paginator],
    );

    expect((string) $view)
        ->toContain('aria-label="Previous results page"')
        ->toContain('aria-label="Next results page"')
        ->not->toContain('aria-label="Go to previous page"')
        ->not->toContain('aria-label="Go to next page"');
});

it('omits empty previous and next label spans', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $empty = $this->blade('<x-hw::pagination :paginator="$paginator" previous-label="" next-label="" />', [
        'paginator' => $paginator,
    ]);

    expect((string) $empty)
        ->toContain('data-slot="pagination-previous"')
        ->toContain('data-slot="pagination-next"')
        ->toContain('data-size="icon"')
        ->not->toContain('data-slot="pagination-previous-label"')
        ->not->toContain('data-slot="pagination-next-label"');

    $null = $this->blade('<x-hw::pagination :paginator="$paginator" :previous-label="null" :next-label="null" />', [
        'paginator' => $paginator,
    ]);

    expect((string) $null)
        ->toContain('data-slot="pagination-previous"')
        ->toContain('data-slot="pagination-next"')
        ->toContain('data-size="icon"')
        ->not->toContain('data-slot="pagination-previous-label"')
        ->not->toContain('data-slot="pagination-next-label"');
});

it('can render only numbered length-aware paginator links', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" display="numbers" />', [
        'paginator' => $paginator,
    ]);

    expect((string) $view)
        ->toContain('data-slot="pagination-link"')
        ->toContain('data-slot="pagination-ellipsis"')
        ->toContain('aria-current="page"')
        ->not->toContain('data-slot="pagination-previous"')
        ->not->toContain('data-slot="pagination-next"');
});

it('can render only previous and next controls from a length-aware paginator', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" display="controls" />', [
        'paginator' => $paginator,
    ]);

    expect((string) $view)
        ->toContain('data-slot="pagination-previous"')
        ->toContain('data-slot="pagination-next"')
        ->toContain('data-slot="pagination-previous-label"')
        ->toContain('data-slot="pagination-next-label"')
        ->not->toContain('data-slot="pagination-link"')
        ->not->toContain('data-slot="pagination-ellipsis"');
});

it('can render icon-only previous and next controls from a length-aware paginator', function () {
    $paginator = new LengthAwarePaginator(range(1, 10), 200, 10, 10, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" display="icons" />', [
        'paginator' => $paginator,
    ]);

    expect((string) $view)
        ->toContain('data-slot="pagination-previous"')
        ->toContain('data-slot="pagination-next"')
        ->toContain('data-size="icon"')
        ->toContain('aria-label="Go to previous page"')
        ->toContain('aria-label="Go to next page"')
        ->not->toContain('data-slot="pagination-link"')
        ->not->toContain('data-slot="pagination-ellipsis"')
        ->not->toContain('data-slot="pagination-previous-label"')
        ->not->toContain('data-slot="pagination-next-label"');
});

it('renders simple paginators with previous and next controls only', function () {
    $paginator = new Paginator(range(1, 11), 10, 2, ['path' => '/users']);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" />', [
        'paginator' => $paginator,
    ]);

    expect((string) $view)
        ->toContain('data-slot="pagination-previous"')
        ->toContain('href="/users?page=1"')
        ->toContain('data-slot="pagination-next"')
        ->toContain('href="/users?page=3"')
        ->not->toContain('data-slot="pagination-link"')
        ->not->toContain('data-slot="pagination-ellipsis"');
});

it('renders cursor paginators with previous and next controls only', function () {
    $paginator = new CursorPaginator([
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
    ], 2, null, ['path' => '/users', 'parameters' => ['id']]);

    $view = $this->blade('<x-hw::pagination :paginator="$paginator" />', [
        'paginator' => $paginator,
    ]);

    expect((string) $view)
        ->toContain('data-slot="pagination-previous"')
        ->toContain('aria-disabled="true"')
        ->toContain('data-slot="pagination-next"')
        ->toContain('href="/users?cursor=')
        ->not->toContain('data-slot="pagination-link"')
        ->not->toContain('data-slot="pagination-ellipsis"');
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

it('gives disabled previous and next controls a link role so their aria label is allowed', function () {
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

    $html = (string) $view;

    preg_match('/<span[^>]*data-slot="pagination-previous"[^>]*>/', $html, $previous);
    preg_match('/<span[^>]*data-slot="pagination-next"[^>]*>/', $html, $next);

    expect($previous[0] ?? '')
        ->toContain('role="link"')
        ->toContain('aria-label="Go to previous page"')
        ->and($next[0] ?? '')
        ->toContain('role="link"')
        ->toContain('aria-label="Go to next page"');
});

it('keeps enabled previous and next controls free of a redundant link role', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::pagination>
            <x-hw::pagination.content>
                <x-hw::pagination.item>
                    <x-hw::pagination.previous href="/users?page=1" />
                </x-hw::pagination.item>
                <x-hw::pagination.item>
                    <x-hw::pagination.next href="/users?page=3" />
                </x-hw::pagination.item>
            </x-hw::pagination.content>
        </x-hw::pagination>
    BLADE);

    $html = (string) $view;

    preg_match('/<a[^>]*data-slot="pagination-previous"[^>]*>/', $html, $previous);
    preg_match('/<a[^>]*data-slot="pagination-next"[^>]*>/', $html, $next);

    expect($previous[0] ?? '')->not->toContain('role=')
        ->and($next[0] ?? '')->not->toContain('role=');
});

it('registers pagination in the component catalog and subcomponent aliases', function () {
    $pagination = HotwireRegistry::make()->component('pagination');

    expect($pagination->key)->toBe('pagination')
        ->and($pagination->controllers)->toBe(['pagination'])
        ->and($pagination->docs)->toBe('docs/components/pagination.md');

    expect(ComponentAliases::subComponents())
        ->toHaveKey('pagination.content')
        ->toHaveKey('pagination.item')
        ->toHaveKey('pagination.link')
        ->toHaveKey('pagination.previous')
        ->toHaveKey('pagination.next')
        ->toHaveKey('pagination.ellipsis');
});
