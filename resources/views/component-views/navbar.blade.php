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
        <nav
            {{ $navbarAttributes }}
        >{{ $slot }}</nav>
    </div>
@else
    <nav
        {{ $navbarAttributes }}
    >{{ $slot }}</nav>
@endif
