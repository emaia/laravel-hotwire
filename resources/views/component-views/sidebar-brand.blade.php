@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    {{ $attributes->merge([
        'href' => $href,
        'aria-label' => $label,
        'data-slot' => 'sidebar-brand',
        'data-sidebar' => 'brand',
    ]) }}
>
    <span data-slot="sidebar-brand-logo" data-sidebar="brand-logo">{{ $slot }}</span>

    @isset($icon)
        @if ($icon->isNotEmpty())
            <span data-slot="sidebar-brand-icon" data-sidebar="brand-icon" aria-hidden="true">{{ $icon }}</span>
        @endif
    @endisset
</{{ $tag }}>
