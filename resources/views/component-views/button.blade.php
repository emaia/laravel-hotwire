@php
    $isButton = $as === 'button';

    $buttonAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => $isButton ? $type : null,
        'data-slot' => $slotName,
        'data-variant' => $variant,
        'data-size' => $size,
    ], $attributes, $stimulus);
@endphp

<{{ $as }}
    {{ $buttonAttributes }}
>{{ $slot }}</{{ $as }}>
