@php
    $isButton = $as === 'button';
    $isAnchor = $as === 'a';
    $disabled = $attributes->has('disabled') && ! in_array($attributes->get('disabled'), [false, null], true);

    $closeAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => $isButton ? $type : null,
        'href' => $isAnchor && ! $disabled ? $attributes->get('href') : null,
        'disabled' => $isButton && $disabled ? true : null,
        'aria-disabled' => $isAnchor && $disabled ? 'true' : $attributes->get('aria-disabled'),
        'tabindex' => $isAnchor && $disabled ? '-1' : $attributes->get('tabindex'),
        'data-slot' => 'modal-close',
        'data-variant' => $variant,
        'data-size' => $size,
        'data-action' => $disabled ? null : 'click->modal#close',
    ], $attributes, except: ['type', 'href', 'disabled', 'aria-disabled', 'tabindex', 'data-slot'], protectedPrefixes: ['data-modal-']);
@endphp

<{{ $as }} {{ $closeAttributes }}>{{ $slot }}</{{ $as }}>
