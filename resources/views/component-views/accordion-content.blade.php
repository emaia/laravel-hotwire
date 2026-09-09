@php
    $contentAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => $slotName,
    ], $attributes, $stimulus);
@endphp

<section
    {{ $contentAttributes }}
>{{ $slot }}</section>
