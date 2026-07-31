@php
    $isDisabled = $disabled || ($href === null && ! $active);
    $tag = ($active || $isDisabled) ? 'span' : 'a';
    $resolvedFrame = $tag === 'a' ? \Emaia\LaravelHotwire\Support\FrameTarget::resolve($frame, $attributes) : null;
    $linkAttributes = $attributes->except(['frame', 'turbo-frame', 'data-turbo-frame', 'data-turbo-stream']);
@endphp

<{{ $tag }}
    {{ $linkAttributes->merge([
        'href' => $tag === 'a' ? $href : null,
        'data-turbo-frame' => $resolvedFrame,
        'data-turbo-stream' => $tag === 'a' && $turboStream ? true : null,
        'aria-current' => $active ? 'page' : null,
        'aria-disabled' => $isDisabled ? 'true' : null,
        'data-slot' => 'pagination-link',
        'data-size' => $size,
        'data-active' => $active ? 'true' : 'false',
        'data-disabled' => $isDisabled ? 'true' : null,
    ]) }}
>{{ $slot }}</{{ $tag }}>
