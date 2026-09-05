@php
    $guardComposition($slot);
@endphp

<nav data-slot="breadcrumb" aria-label="{{ $label }}" {{ $attributes->except('frame') }}>
    @if ($items !== [])
        <x-hw::breadcrumb.list>
            @foreach ($normalizedItems() as $item)
                <x-hw::breadcrumb.item>
                    @if ($item['type'] === 'ellipsis')
                        <x-hw::breadcrumb.ellipsis :label="$item['label']" />
                    @elseif ($item['current'] || $item['href'] === null)
                        <x-hw::breadcrumb.page>{{ $item['label'] }}</x-hw::breadcrumb.page>
                    @else
                        <x-hw::breadcrumb.link :href="$item['href']" :frame="$item['frame']">{{ $item['label'] }}</x-hw::breadcrumb.link>
                    @endif
                </x-hw::breadcrumb.item>

                @unless ($loop->last)
                    <x-hw::breadcrumb.separator />
                @endunless
            @endforeach
        </x-hw::breadcrumb.list>
    @else
        {{ $slot }}
    @endif
</nav>
