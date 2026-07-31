@php
    $disabled = $attributes->has('disabled') && ! in_array($attributes->get('disabled'), [false, null], true);
    $itemAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'item',
        'data-variant' => $variant,
        'data-size' => $size,
        'type' => $as === 'button' ? $type : null,
        'href' => $as === 'a' && ! $disabled ? $attributes->get('href') : null,
        'disabled' => $as === 'button' && $disabled ? true : null,
        'aria-disabled' => $as === 'a' && $disabled ? 'true' : $attributes->get('aria-disabled'),
        'tabindex' => $as === 'a' && $disabled ? '-1' : $attributes->get('tabindex'),
    ], $attributes, except: ['data-slot', 'data-variant', 'data-size', 'type', 'href', 'disabled', 'aria-disabled', 'tabindex']);
@endphp

<{{ $as }} {{ $itemAttributes }}>{{ $slot }}</{{ $as }}>
