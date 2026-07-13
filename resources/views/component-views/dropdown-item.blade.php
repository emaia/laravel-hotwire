@php
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    {{ $attributes->merge([
        'href' => $href,
        'type' => $href ? null : $type,
        'data-slot' => 'dropdown-item',
        'data-variant' => $variant,
        'data-inset' => $inset ? 'true' : null,
        'data-disabled' => $disabled ? 'true' : null,
        'disabled' => $disabled && ! $href ? true : null,
        'aria-disabled' => $disabled ? 'true' : null,
        'tabindex' => $disabled && $href ? '-1' : null,
    ]) }}
>{{ $slot }}</{{ $tag }}>
