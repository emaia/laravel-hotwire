@php
    $tag = $href !== null ? 'a' : 'button';
    $resolvedFrame = $tag === 'a' ? \Emaia\LaravelHotwire\Support\FrameTarget::resolve($frame, $attributes) : null;
    $buttonAttributes = $attributes->except(['frame', 'data-turbo-frame']);
@endphp

<{{ $tag }}
    {{ $buttonAttributes->merge([
        'href' => $tag === 'a' ? $href : null,
        'data-turbo-frame' => $resolvedFrame,
        'type' => $tag === 'button' ? $type : null,
        'data-slot' => 'sidebar-menu-button',
        'data-sidebar' => 'menu-button',
        'data-variant' => $variant,
        'data-size' => $size,
        'data-active' => $active ? 'true' : 'false',
    ]) }}
>{{ $slot }}</{{ $tag }}>
