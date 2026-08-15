@php
    $tag = $href !== null ? 'a' : 'div';
    $resolvedFrame = $tag === 'a' ? \Emaia\LaravelHotwire\Support\FrameTarget::resolve($frame, $attributes) : null;
    $brandAttributes = $attributes->except(['frame', 'data-turbo-frame']);
@endphp

<{{ $tag }}
    {{ $brandAttributes->merge([
        'href' => $tag === 'a' ? $href : null,
        'data-turbo-frame' => $resolvedFrame,
        'aria-label' => $tag === 'a' ? $label : null,
        'data-slot' => 'sidebar-brand',
        'data-sidebar' => 'brand',
    ]) }}
>
    <span data-slot="sidebar-brand-logo" data-sidebar="brand-logo">{{ $slot }}</span>

    @isset($icon)
        @if ($icon->isNotEmpty())
            <span data-slot="sidebar-brand-icon" data-sidebar="brand-icon" aria-hidden="true">{{ $icon }}</span>
        @endif
    @endisset
</{{ $tag }}>
