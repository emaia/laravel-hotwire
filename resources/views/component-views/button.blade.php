@php
    $isButton = $as === 'button';
    $isAnchor = $as === 'a';
    $disabled = $attributes->has('disabled') && ! in_array($attributes->get('disabled'), [false, null], true);
    $resolvedFrame = $disabled ? null : \Emaia\LaravelHotwire\Support\FrameTarget::resolve($frame, $attributes);

    $buttonAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => $isButton ? $type : null,
        'href' => $isAnchor && ! $disabled ? $attributes->get('href') : null,
        'disabled' => $isButton && $disabled ? true : null,
        'aria-disabled' => $isAnchor && $disabled ? 'true' : $attributes->get('aria-disabled'),
        'tabindex' => $isAnchor && $disabled ? '-1' : $attributes->get('tabindex'),
        'data-slot' => $slotName,
        'data-variant' => $variant,
        'data-size' => $size,
        'data-turbo-frame' => $resolvedFrame,
        'data-controller' => $buttonController,
        'data-action' => $buttonAction,
        'data-tooltip-content-value' => $hasTooltip ? $tooltip : null,
        'data-tooltip-side-value' => $hasTooltip ? $tooltipSide : null,
        'data-tooltip-align-value' => $hasTooltip ? $tooltipAlign : null,
        'data-tooltip-motion-value' => $hasTooltip ? $tooltipMotion : null,
        'data-tooltip-enabled-when-value' => $hasTooltip ? $tooltipEnabledWhen : null,
    ], $attributes, $stimulus, except: ['type', 'href', 'disabled', 'aria-disabled', 'tabindex', 'frame', 'data-turbo-frame', 'hotkey', 'tooltip', 'tooltip-side', 'tooltip-align', 'tooltip-motion', 'tooltip-enabled-when'], protectedPrefixes: $buttonProtectedPrefixes);
@endphp

<{{ $as }}
    {{ $buttonAttributes }}
>{{ $slot }}</{{ $as }}>
