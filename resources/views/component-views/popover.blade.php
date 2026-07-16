@php
    $popoverAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'popover',
        'data-controller' => 'popover',
        'data-popover-open-value' => $open ? 'true' : null,
        'data-popover-side-value' => $side,
        'data-popover-align-value' => $align,
        'data-popover-side-offset-value' => $sideOffset,
        'data-popover-align-offset-value' => $alignOffset,
        'data-popover-strategy-value' => $strategy,
        'data-popover-flip-value' => $flip ? 'true' : 'false',
        'data-popover-shift-value' => $shift ? 'true' : 'false',
    ], $attributes, $stimulus, protectedPrefixes: ['data-popover-']);
@endphp

<div {{ $popoverAttributes }}>
    {{ $slot }}
</div>
