@php
    $isButton = $as === 'button';

    $closeAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => $isButton ? $type : null,
        'data-slot' => 'modal-close',
        'data-variant' => $variant,
        'data-size' => $size,
        'data-action' => 'modal#close',
    ], $attributes, except: ['type', 'data-slot'], protectedPrefixes: ['data-modal-']);
@endphp

<{{ $as }} {{ $closeAttributes }}>{{ $slot }}</{{ $as }}>
