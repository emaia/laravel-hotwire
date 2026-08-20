@php
    $hoverCardAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'hover-card',
        'data-controller' => 'hover-card',
        'data-hover-card-open-value' => $hoverCardOpen ? 'true' : null,
        'data-hover-card-open-delay-value' => $hoverCardOpenDelay,
        'data-hover-card-close-delay-value' => $hoverCardCloseDelay,
        'data-hover-card-side-value' => $hoverCardSide,
        'data-hover-card-align-value' => $hoverCardAlign,
        'data-hover-card-side-offset-value' => $hoverCardSideOffset,
        'data-hover-card-align-offset-value' => $hoverCardAlignOffset,
        'data-hover-card-strategy-value' => $hoverCardStrategy,
        'data-hover-card-flip-value' => $hoverCardFlip ? 'true' : 'false',
        'data-hover-card-shift-value' => $hoverCardShift ? 'true' : 'false',
    ], $attributes, $hoverCardStimulus, protectedPrefixes: ['data-hover-card-']);
@endphp

<div {{ $hoverCardAttributes }}>
    {{ $slot }}
</div>
