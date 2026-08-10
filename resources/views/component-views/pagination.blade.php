@php
    $usesIncrementalPagination = $usesIncrementalLoading();

    $paginationAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'role' => 'navigation',
        'aria-label' => $label,
        'data-slot' => 'pagination',
        'data-controller' => $usesIncrementalPagination ? 'pagination' : null,
        'data-pagination-append-to-value' => $usesIncrementalPagination ? $appendTo : null,
        'data-pagination-infinite-value' => $usesIncrementalPagination && $infinite ? 'true' : null,
        'data-pagination-loading-label-value' => $usesIncrementalPagination ? $loadingLabelValue() : null,
        'data-pagination-loaded-label-value' => $usesIncrementalPagination ? $loadedLabel : null,
        'data-pagination-error-label-value' => $usesIncrementalPagination ? $errorLabel : null,
        'data-pagination-scroll-to-value' => $usesIncrementalPagination ? $scrollTo : null,
        'data-pagination-root-margin-value' => $usesIncrementalPagination ? $rootMargin : null,
        'data-pagination-threshold-value' => $usesIncrementalPagination ? $threshold : null,
    ], $attributes->except(['frame', 'turbo-frame']), $stimulus, protectedPrefixes: ['data-slot', 'data-pagination-']);
@endphp

<nav {{ $paginationAttributes }}>
    @if ($paginator !== null)
        @if ($usesIncrementalPagination)
            <span data-slot="pagination-status" data-pagination-target="status" role="status" aria-live="polite" aria-atomic="true"></span>
        @endif

        <x-hw::pagination.content>
            @foreach ($links as $link)
                <x-hw::pagination.item>
                    @if ($link['type'] === 'previous')
                        <x-hw::pagination.previous
                            :href="$link['url']"
                            :disabled="$link['disabled']"
                            :label="$link['label']"
                            :size="$link['size']"
                            :frame="$frame"
                            :turbo-stream="$turboStream"
                            :aria-label="$previousAriaLabel"
                        />
                    @elseif ($link['type'] === 'next')
                        <x-hw::pagination.next
                            :href="$link['url']"
                            :disabled="$link['disabled']"
                            :label="$link['label']"
                            :size="$link['size']"
                            :frame="$frame"
                            :turbo-stream="$turboStream"
                            :aria-label="$usesIncrementalPagination ? $loadMoreAriaLabel() : $nextAriaLabel"
                            :loading-label="$usesIncrementalPagination ? $loadingLabelValue() : null"
                            :icon-name="$usesIncrementalPagination ? 'chevron-down' : 'chevron-right'"
                            :icon="$usesIncrementalPagination ? ($loadMoreIcon ?? null) : null"
                            :data-pagination-target="$usesIncrementalPagination && ! $link['disabled'] ? 'next' : null"
                            :data-action="$usesIncrementalPagination && ! $link['disabled'] ? 'click->pagination#load' : null"
                        />
                    @elseif ($link['type'] === 'ellipsis')
                        <x-hw::pagination.ellipsis :label="$link['label']" />
                    @else
                        <x-hw::pagination.link
                            :href="$link['url']"
                            :active="$link['active']"
                            :disabled="$link['disabled']"
                            :frame="$frame"
                            :turbo-stream="$turboStream"
                        >{{ $link['label'] }}</x-hw::pagination.link>
                    @endif
                </x-hw::pagination.item>
            @endforeach
        </x-hw::pagination.content>
    @else
        {{ $slot }}
    @endif
</nav>
