@aware(['frame' => null])

@php
    $isButton = $as === 'button';
    $isAnchor = $as === 'a';
    $disabled = $attributes->has('disabled') && ! in_array($attributes->get('disabled'), [false, null], true);
    $frameTarget = $attributes->has('frame') ? $attributes->get('frame') : $frame;
    $resolvedFrame = $isAnchor && ! $disabled
        ? \Emaia\LaravelHotwire\Support\FrameTarget::resolve($frameTarget, $attributes)
        : null;

    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => $isButton ? $type : null,
        'href' => $isAnchor && ! $disabled ? $attributes->get('href') : null,
        'disabled' => $isButton && $disabled ? true : null,
        'aria-disabled' => $isAnchor && $disabled ? 'true' : $attributes->get('aria-disabled'),
        'tabindex' => $isAnchor && $disabled ? '-1' : $attributes->get('tabindex'),
        'data-turbo-frame' => $resolvedFrame,
        'data-slot' => 'modal-trigger',
        'data-variant' => $variant,
        'data-size' => $size,
        'data-action' => $disabled ? null : 'click->modal#open',
        'aria-haspopup' => 'dialog',
    ], $attributes, except: ['type', 'href', 'disabled', 'aria-disabled', 'tabindex', 'frame', 'data-turbo-frame', 'data-slot', 'aria-haspopup'], protectedPrefixes: ['data-modal-']);
@endphp

<{{ $as }} {{ $triggerAttributes }}>{{ $slot }}</{{ $as }}>
