<{{ $tag }}
    {{ $attributes->merge([
        'data-slot' => $slotName,
        'data-sidebar' => $sidebarName,
    ]) }}
>{{ $slot }}</{{ $tag }}>
