@php
    $contentAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'accordion-content',
    ], $attributes, $stimulus);
@endphp

<section
    {{ $contentAttributes }}
>{{ $slot }}</section>
