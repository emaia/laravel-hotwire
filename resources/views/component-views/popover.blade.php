@php
    $popoverAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'popover',
        'data-controller' => 'popover',
        'data-popover-open-value' => $popoverOpen ? 'true' : null,
        'data-popover-side-value' => $popoverSide,
        'data-popover-align-value' => $popoverAlign,
        'data-popover-side-offset-value' => $popoverSideOffset,
        'data-popover-align-offset-value' => $popoverAlignOffset,
        'data-popover-strategy-value' => $popoverStrategy,
        'data-popover-flip-value' => $popoverFlip ? 'true' : 'false',
        'data-popover-shift-value' => $popoverShift ? 'true' : 'false',
    ], $attributes, $popoverStimulus, protectedPrefixes: ['data-popover-']);
@endphp

<div {{ $popoverAttributes }}>
    {{ $slot }}
</div>
