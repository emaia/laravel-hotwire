@aware(['hoverCardId' => null, 'hoverCardOpen' => false])

@php
    if ($hoverCardId === null) {
        throw new InvalidArgumentException('Hover Card trigger must be rendered inside a Hover Card root.');
    }

    $disabled = $attributes->has('disabled') && ! in_array($attributes->get('disabled'), [false, null], true);
    $nativeFocusable = ! $disabled && ($as === 'button'
        || ($as === 'a' && trim((string) $attributes->get('href', '')) !== ''))
        || $attributes->get('tabindex') !== null;

    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => $as === 'button' ? $type : null,
        'data-slot' => 'hover-card-trigger',
        'data-variant' => $variant,
        'data-size' => $size,
        'href' => $as === 'a' && ! $disabled ? $attributes->get('href') : null,
        'disabled' => $as === 'button' && $disabled ? true : null,
        'aria-disabled' => $as === 'a' && $disabled ? 'true' : $attributes->get('aria-disabled'),
        'data-hover-card-target' => 'trigger',
        'data-action' => $disabled ? null : 'mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut',
        'aria-describedby' => $hoverCardId,
        'aria-expanded' => $hoverCardOpen ? 'true' : 'false',
        'data-hover-card-state' => $hoverCardOpen ? 'open' : 'closed',
        'tabindex' => $disabled ? '-1' : $attributes->get('tabindex', $nativeFocusable ? null : '0'),
    ], $attributes, except: ['type', 'href', 'disabled', 'aria-disabled', 'tabindex', 'data-slot', 'aria-describedby', 'aria-expanded'], protectedPrefixes: ['data-hover-card-']);
@endphp

<{{ $as }} {{ $triggerAttributes }}>{{ $slot }}</{{ $as }}>
