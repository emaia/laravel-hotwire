@php
    use Emaia\LaravelHotwire\Support\FrameTarget;
    use Emaia\LaravelHotwire\Support\OverlayPresentation;

    $tag = $dropdownItemHref !== null ? 'a' : 'button';
    $targetFrame = $tag === 'a' ? FrameTarget::resolve($dropdownItemFrame, $attributes) : null;
    $resolvedFrame = $dropdownItemDisabled ? null : $targetFrame;
    $presentation = OverlayPresentation::resolve(
        OverlayPresentation::inferFamily($dropdownItemOverlay, $targetFrame, $dropdownItemOverlaySide, $dropdownItemOverlaySize),
        $dropdownItemOverlaySide,
        $dropdownItemOverlaySize,
    );
    $opensOverlay = $presentation['family'] !== null || $dropdownItemOverlayReturn !== null;
    if ($opensOverlay && ($tag !== 'a' || (! $dropdownItemDisabled && $resolvedFrame === null))) {
        throw new InvalidArgumentException('Overlay options require a framed link item.');
    }
    $overlayActive = $opensOverlay && ! $dropdownItemDisabled;
    $itemAttributes = $attributes->except(['frame', 'data-turbo-frame', 'overlay', 'overlay-side', 'overlay-size', 'overlay-return', 'data-overlay-return-to']);
@endphp

<{{ $tag }}
    {{ $itemAttributes->merge([
        'href' => $tag === 'a' && ! $dropdownItemDisabled ? $dropdownItemHref : null,
        'type' => $tag === 'button' ? $dropdownItemType : null,
        'data-turbo-frame' => $resolvedFrame,
        'data-overlay-family' => $overlayActive ? $presentation['family'] : null,
        'data-overlay-side' => $overlayActive ? $presentation['side'] : null,
        'data-overlay-size' => $overlayActive ? $presentation['size'] : null,
        'data-overlay-return-to' => $overlayActive ? $dropdownItemOverlayReturn : null,
        'data-slot' => $slotName,
        'data-variant' => $dropdownItemVariant,
        'data-inset' => $dropdownItemInset ? 'true' : null,
        'data-disabled' => $dropdownItemDisabled ? 'true' : null,
        'disabled' => $dropdownItemDisabled && $tag === 'button' ? true : null,
        'aria-disabled' => $dropdownItemDisabled ? 'true' : null,
        'tabindex' => $dropdownItemDisabled && $tag === 'a' ? '-1' : null,
    ]) }}
>{{ $slot }}</{{ $tag }}>
