@php
    $isDisabled = $disabled || ($href === null && ! $active);
    $tag = ($active || $isDisabled) ? 'span' : 'a';
    $linkAttributes = $tag === 'a' ? $attributes : $attributes->except(['data-turbo-frame', 'data-turbo-stream']);
@endphp

<{{ $tag }}
    {{ $linkAttributes->merge([
        'href' => $tag === 'a' ? $href : null,
        'data-turbo-frame' => $tag === 'a' ? $turboFrame : null,
        'data-turbo-stream' => $tag === 'a' && $turboStream ? true : null,
        'aria-current' => $active ? 'page' : null,
        'aria-disabled' => $isDisabled ? 'true' : null,
        'data-slot' => 'pagination-link',
        'data-size' => $size,
        'data-active' => $active ? 'true' : 'false',
        'data-disabled' => $isDisabled ? 'true' : null,
    ]) }}
>{{ $slot }}</{{ $tag }}>
