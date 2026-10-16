@php
    use Emaia\LaravelHotwire\Support\FrameTarget;
    use Emaia\LaravelHotwire\Support\OverlayPresentation;
    use Emaia\LaravelHotwire\Support\StimulusAttributes;

    $isButton = $as === 'button';
    $isAnchor = $as === 'a';
    $disabled = $attributes->has('disabled') && ! in_array($attributes->get('disabled'), [false, null], true);
    $targetFrame = FrameTarget::resolve($frame, $attributes);
    $resolvedFrame = $disabled ? null : $targetFrame;
    $presentation = OverlayPresentation::resolve(
        OverlayPresentation::inferFamily($overlay, $targetFrame, $overlaySide, $overlaySize),
        $overlaySide,
        $overlaySize,
    );
    $opensOverlay = $presentation['family'] !== null || $overlayReturn !== null;
    if ($opensOverlay && (! $isAnchor || (! $disabled && ($resolvedFrame === null || ! $attributes->get('href'))))) {
        throw new InvalidArgumentException('Overlay options require a framed anchor button.');
    }

    $buttonAttributes = StimulusAttributes::merge([
        'type' => $isButton ? $type : null,
        'href' => $isAnchor && ! $disabled ? $attributes->get('href') : null,
        'disabled' => $isButton && $disabled ? true : null,
        'aria-disabled' => $isAnchor && $disabled ? 'true' : $attributes->get('aria-disabled'),
        'tabindex' => $isAnchor && $disabled ? '-1' : $attributes->get('tabindex'),
        'data-slot' => $slotName,
        'data-variant' => $variant,
        'data-size' => $size,
        'data-turbo-frame' => $resolvedFrame,
        'data-overlay-family' => $isAnchor && ! $disabled ? $presentation['family'] : null,
        'data-overlay-side' => $isAnchor && ! $disabled ? $presentation['side'] : null,
        'data-overlay-size' => $isAnchor && ! $disabled ? $presentation['size'] : null,
        'data-overlay-return-to' => $isAnchor && ! $disabled ? $overlayReturn : null,
        'data-controller' => $buttonController,
        'data-action' => $buttonAction,
        'data-tooltip-side-value' => $hasTooltip ? $tooltipSide : null,
        'data-tooltip-align-value' => $hasTooltip ? $tooltipAlign : null,
        'data-tooltip-motion-value' => $hasTooltip ? $tooltipMotion : null,
        'data-tooltip-enabled-when-value' => $hasTooltip ? $tooltipEnabledWhen : null,
    ], $attributes, $stimulus, except: ['type', 'href', 'disabled', 'aria-disabled', 'tabindex', 'frame', 'data-turbo-frame', 'hotkey', 'tooltip', 'tooltip-side', 'tooltip-align', 'tooltip-motion', 'tooltip-enabled-when', 'data-tooltip-content-value', 'overlay', 'overlay-side', 'overlay-size', 'overlay-return', 'data-overlay-return-to'], protectedPrefixes: $buttonProtectedPrefixes);
@endphp

<{{ $as }} {{ $buttonAttributes }}>
    {{ $slot }}
    @if ($hasTooltip)
        <x-hw::tooltip>{{ $tooltip }}</x-hw::tooltip>
    @endif
</{{ $as }}>
