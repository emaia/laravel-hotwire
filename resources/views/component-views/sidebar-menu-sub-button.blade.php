@php
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    {{ $attributes->merge([
        'href' => $href,
        'type' => $href ? null : $type,
        'data-slot' => 'sidebar-menu-sub-button',
        'data-sidebar' => 'menu-sub-button',
        'data-size' => $size,
        'data-active' => $active ? 'true' : 'false',
    ]) }}
>{{ $slot }}</{{ $tag }}>
