@php
    $isDisabled = $disabled || ($href === null && ! $active);
    $tag = ($active || $isDisabled) ? 'span' : 'a';
@endphp

<{{ $tag }}
    {{ $attributes->merge([
        'href' => $tag === 'a' ? $href : null,
        'data-turbo-frame' => $tag === 'a' ? $turboFrame : null,
        'aria-current' => $active ? 'page' : null,
        'aria-disabled' => $isDisabled ? 'true' : null,
        'data-slot' => 'pagination-link',
        'data-size' => $size,
        'data-active' => $active ? 'true' : 'false',
        'data-disabled' => $isDisabled ? 'true' : null,
    ]) }}
>{{ $slot }}</{{ $tag }}>
