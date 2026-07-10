@php
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    {{ $attributes->merge([
        'href' => $href,
        'type' => $href ? null : $type,
        'data-slot' => 'sidebar-menu-button',
        'data-sidebar' => 'menu-button',
        'data-variant' => $variant,
        'data-size' => $size,
        'data-active' => $active ? 'true' : 'false',
    ]) }}
>{{ $slot }}</{{ $tag }}>
