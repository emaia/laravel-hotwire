@php
    $scrollProgressAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => $slotName,
        'data-controller' => 'scroll-progress',
        'data-scroll-progress-throttle-delay-value' => $throttleDelay,
    ], $attributes, $stimulus, protectedPrefixes: ['data-scroll-progress-']);
@endphp

<div
    {{ $scrollProgressAttributes }}
></div>
