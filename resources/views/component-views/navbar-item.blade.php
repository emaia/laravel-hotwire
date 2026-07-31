@php
    $resolvedFrame = $tag === 'a' && ! $disabled
        ? \Emaia\LaravelHotwire\Support\FrameTarget::resolve($frame, $attributes)
        : null;
    $itemAttributes = $attributes->except(['frame', 'data-turbo-frame'])->merge([
        'href' => $tag === 'a' && ! $disabled ? $href : null,
        'type' => $tag === 'button' ? $type : null,
        'data-slot' => 'navbar-item',
        'data-current' => $current ? 'true' : 'false',
        'data-disabled' => $disabled ? 'true' : null,
        'aria-current' => $tag === 'a' && $current ? 'page' : null,
        'aria-disabled' => $tag === 'a' && $disabled ? 'true' : null,
        'tabindex' => $tag === 'a' && $disabled ? '-1' : null,
        'disabled' => $tag === 'button' && $disabled ? true : null,
        'data-turbo-frame' => $resolvedFrame,
    ]);
@endphp

<{{ $tag }}
    {{ $itemAttributes }}
>{{ $slot }}</{{ $tag }}>
