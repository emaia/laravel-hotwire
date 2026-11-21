@php
    $guardComposition($slot);
@endphp

<nav data-slot="{{ $slotName }}" aria-label="{{ $label }}" @if ($compactPage) data-collapse-below="{{ $collapseBelow }}" @endif {{ $attributes->except(['frame', 'data-collapse-below']) }}>
    @if ($items !== [])
        <x-hw::breadcrumb.list :data-breadcrumb-layout="$compactPage ? 'expanded' : null">
            @foreach ($trailItems as $item)
                <x-hw::breadcrumb.item>
                    @if ($item['type'] === 'ellipsis')
                        <x-hw::breadcrumb.ellipsis :label="$item['label']" />
                    @elseif ($item['current'])
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

        @if ($compactPage)
            <x-hw::breadcrumb.list data-breadcrumb-layout="compact">
                <x-hw::breadcrumb.item>
                    <x-hw::dropdown>
                        <x-hw::dropdown.trigger :aria-label="$ellipsisLabel">
                            <x-hw::breadcrumb.ellipsis aria-hidden="true" />
                        </x-hw::dropdown.trigger>
                        <x-hw::dropdown.content side="bottom" align="start" strategy="fixed" mobile-side="bottom" :mobile-media="$compactMedia">
                            @foreach ($compactLinks as $link)
                                <x-hw::dropdown.item :href="$link['href']" :frame="$link['frame']">
                                    {{ $link['label'] }}
                                </x-hw::dropdown.item>
                            @endforeach
                        </x-hw::dropdown.content>
                    </x-hw::dropdown>
                </x-hw::breadcrumb.item>
                <x-hw::breadcrumb.separator />
                <x-hw::breadcrumb.item>
                    <x-hw::breadcrumb.page>{{ $compactLabel ?? $compactPage['label'] }}</x-hw::breadcrumb.page>
                </x-hw::breadcrumb.item>
            </x-hw::breadcrumb.list>
        @endif
    @else
        {{ $slot }}
    @endif
</nav>
