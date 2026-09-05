@php
    $navbarAttributes = $attributes->except(['sticky', 'sticky-side', 'sticky-offset'])->merge([
        'data-slot' => 'navbar',
        'data-variant' => $variant,
        'data-orientation' => $orientation,
        'data-overflow' => $overflow,
    ]);
@endphp

@if ($sticky)
    <div
        data-slot="sticky"
        data-side="{{ $stickySide }}"
        data-surface="true"
        style="--sticky-offset: {{ $stickyOffset }};"
    >
@endif

<nav
    {{ $navbarAttributes }}
>
    @foreach ($navbarItems as $item)
        <x-hw::navbar.item
            :href="$item['href'] ?? null"
            :current="$item['current'] ?? false"
            :disabled="$item['disabled'] ?? false"
            :as="$item['as'] ?? null"
            :type="$item['type'] ?? 'button'"
            :frame="$item['frame'] ?? null"
        >{{ $item['label'] }}</x-hw::navbar.item>
    @endforeach

    {{ $slot }}
</nav>

@if ($sticky)
    </div>
@endif
