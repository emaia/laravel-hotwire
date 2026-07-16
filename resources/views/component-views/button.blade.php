@php
    $isButton = $as === 'button';

    $buttonAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => $isButton ? $type : null,
        'data-slot' => $slotName,
        'data-variant' => $variant,
        'data-size' => $size,
        'data-turbo-frame' => $frame,
        'data-controller' => $buttonController,
        'data-action' => $buttonAction,
        'data-tooltip-content-value' => $hasTooltip ? $tooltip : null,
        'data-tooltip-side-value' => $hasTooltip ? $tooltipSide : null,
        'data-tooltip-align-value' => $hasTooltip ? $tooltipAlign : null,
        'data-tooltip-enabled-when-value' => $hasTooltip ? $tooltipEnabledWhen : null,
    ], $attributes, $stimulus, except: ['frame', 'hotkey', 'tooltip', 'tooltip-side', 'tooltip-align', 'tooltip-enabled-when'], protectedPrefixes: $buttonProtectedPrefixes);
@endphp

<{{ $as }}
    {{ $buttonAttributes }}
>{{ $slot }}</{{ $as }}>
