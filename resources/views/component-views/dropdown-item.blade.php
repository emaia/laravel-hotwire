@php
    $tag = $href !== null ? 'a' : 'button';
    $resolvedFrame = $tag === 'a' && ! $disabled
        ? \Emaia\LaravelHotwire\Support\FrameTarget::resolve($frame, $attributes)
        : null;
    $itemAttributes = $attributes->except(['frame', 'data-turbo-frame']);
@endphp

<{{ $tag }}
    {{ $itemAttributes->merge([
        'href' => $tag === 'a' && ! $disabled ? $href : null,
        'type' => $tag === 'button' ? $type : null,
        'data-turbo-frame' => $resolvedFrame,
        'data-slot' => 'dropdown-item',
        'data-variant' => $variant,
        'data-inset' => $inset ? 'true' : null,
        'data-disabled' => $disabled ? 'true' : null,
        'disabled' => $disabled && $tag === 'button' ? true : null,
        'aria-disabled' => $disabled ? 'true' : null,
        'tabindex' => $disabled && $tag === 'a' ? '-1' : null,
    ]) }}
>{{ $slot }}</{{ $tag }}>
