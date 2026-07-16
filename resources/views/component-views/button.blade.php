@php
    $isButton = $as === 'button';

    $buttonAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => $isButton ? $type : null,
        'data-slot' => $slotName,
        'data-variant' => $variant,
        'data-size' => $size,
        'data-turbo-frame' => $frame,
    ], $attributes, $stimulus);
@endphp

<{{ $as }}
    {{ $buttonAttributes }}
>{{ $slot }}</{{ $as }}>
