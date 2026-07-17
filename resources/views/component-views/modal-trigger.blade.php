@php
    $isButton = $as === 'button';

    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => $isButton ? $type : null,
        'data-slot' => 'modal-trigger',
        'data-variant' => $variant,
        'data-size' => $size,
        'data-action' => 'modal#open',
        'aria-haspopup' => 'dialog',
    ], $attributes, except: ['type', 'data-slot', 'aria-haspopup'], protectedPrefixes: ['data-modal-']);
@endphp

<{{ $as }} {{ $triggerAttributes }}>{{ $slot }}</{{ $as }}>
