@aware(['id' => '', 'open' => false])

@php
    $nativeFocusable = in_array($as, ['a', 'button', 'input', 'select', 'textarea', 'summary'], true)
        || $attributes->has('href')
        || $attributes->has('tabindex');

    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => $as === 'button' ? $type : null,
        'data-slot' => 'hover-card-trigger',
        'data-variant' => $variant,
        'data-size' => $size,
        'data-hover-card-target' => 'trigger',
        'data-action' => 'mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut',
        'aria-describedby' => $id,
        'aria-expanded' => $open ? 'true' : 'false',
        'tabindex' => $nativeFocusable ? null : '0',
    ], $attributes, except: ['data-slot', 'aria-describedby', 'aria-expanded'], protectedPrefixes: ['data-hover-card-']);
@endphp

<{{ $as }} {{ $triggerAttributes }}>{{ $slot }}</{{ $as }}>
