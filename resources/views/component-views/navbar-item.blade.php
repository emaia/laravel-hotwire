@php
    $itemAttributes = $attributes->merge([
        'href' => $tag === 'a' && ! $disabled ? $href : null,
        'type' => $tag === 'button' ? $type : null,
        'data-slot' => 'navbar-item',
        'data-current' => $current ? 'true' : 'false',
        'data-disabled' => $disabled ? 'true' : null,
        'aria-current' => $tag === 'a' && $current ? 'page' : null,
        'aria-disabled' => $tag === 'a' && $disabled ? 'true' : null,
        'tabindex' => $tag === 'a' && $disabled ? '-1' : null,
        'disabled' => $tag === 'button' && $disabled ? true : null,
    ]);
@endphp

<{{ $tag }}
    {{ $itemAttributes }}
>{{ $slot }}</{{ $tag }}>
