@php
    $hoverCardAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'hover-card',
        'data-controller' => 'hover-card',
        'data-hover-card-open-value' => $open ? 'true' : null,
        'data-hover-card-open-delay-value' => $openDelay,
        'data-hover-card-close-delay-value' => $closeDelay,
        'data-hover-card-side-value' => $side,
        'data-hover-card-align-value' => $align,
        'data-hover-card-side-offset-value' => $sideOffset,
        'data-hover-card-align-offset-value' => $alignOffset,
        'data-hover-card-strategy-value' => $strategy,
        'data-hover-card-flip-value' => $flip ? 'true' : 'false',
        'data-hover-card-shift-value' => $shift ? 'true' : 'false',
    ], $attributes, $stimulus, protectedPrefixes: ['data-hover-card-']);
@endphp

<div {{ $hoverCardAttributes }}>
    {{ $slot }}
</div>
