<nav {{ $attributes->merge(['role' => 'navigation', 'aria-label' => $label, 'data-slot' => 'pagination']) }}>
    @if ($paginator !== null)
        <x-hw::pagination.content>
            @foreach ($links as $link)
                <x-hw::pagination.item>
                    @if ($link['type'] === 'previous')
                        <x-hw::pagination.previous
                            :href="$link['url']"
                            :disabled="$link['disabled']"
                            :label="$link['label']"
                            :size="$link['size']"
                            :turbo-frame="$turboFrame"
                            :turbo-stream="$turboStream"
                            :aria-label="$previousAriaLabel"
                        />
                    @elseif ($link['type'] === 'next')
                        <x-hw::pagination.next
                            :href="$link['url']"
                            :disabled="$link['disabled']"
                            :label="$link['label']"
                            :size="$link['size']"
                            :turbo-frame="$turboFrame"
                            :turbo-stream="$turboStream"
                            :aria-label="$nextAriaLabel"
                        />
                    @elseif ($link['type'] === 'ellipsis')
                        <x-hw::pagination.ellipsis :label="$link['label']" />
                    @else
                        <x-hw::pagination.link
                            :href="$link['url']"
                            :active="$link['active']"
                            :disabled="$link['disabled']"
                            :turbo-frame="$turboFrame"
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
