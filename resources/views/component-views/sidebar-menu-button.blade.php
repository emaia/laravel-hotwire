@php
    $tag = $href !== null ? 'a' : 'button';
    $resolvedFrame = $tag === 'a' ? \Emaia\LaravelHotwire\Support\FrameTarget::resolve($frame, $attributes) : null;
    $buttonAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'href' => $tag === 'a' ? $href : null,
        'data-turbo-frame' => $resolvedFrame,
        'type' => $tag === 'button' ? $type : null,
        'data-slot' => $slotName,
        'data-sidebar' => 'menu-button',
        'data-variant' => $variant,
        'data-size' => $size,
        'data-active' => $active ? 'true' : 'false',
        'data-controller' => $hasTooltip ? 'tooltip' : null,
        'data-tooltip-side-value' => $hasTooltip ? $tooltipSide : null,
        'data-tooltip-motion-value' => $hasTooltip ? $tooltipMotion : null,
        'data-tooltip-enabled-when-value' => $hasTooltip ? $tooltipEnabledWhen : null,
    ], $attributes, except: ['frame', 'data-turbo-frame', 'tooltip', 'tooltip-side', 'tooltip-motion', 'tooltip-enabled-when', 'data-tooltip-content-value'], protectedPrefixes: array_values(array_filter([
        $hasTooltip ? 'data-tooltip-' : null,
    ])));
@endphp

<{{ $tag }}
    {{ $buttonAttributes }}
>
    {{ $slot }}
    @if ($hasTooltip)
        <x-hw::tooltip>{{ $tooltip }}</x-hw::tooltip>
    @endif
</{{ $tag }}>
