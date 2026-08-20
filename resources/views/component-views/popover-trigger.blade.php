@aware(['popoverId' => null, 'popoverOpen' => false])

@php
    if ($popoverId === null) {
        throw new InvalidArgumentException('Popover trigger must be rendered inside a Popover root.');
    }

    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => 'button',
        'data-slot' => 'popover-trigger',
        'data-popover-target' => 'trigger',
        'data-action' => 'popover#toggle',
        'aria-haspopup' => 'dialog',
        'aria-expanded' => $popoverOpen ? 'true' : 'false',
        'aria-controls' => $popoverId,
        'data-popover-state' => $popoverOpen ? 'open' : 'closed',
    ], $attributes, except: ['type', 'data-slot', 'aria-haspopup', 'aria-expanded', 'aria-controls'], protectedPrefixes: ['data-popover-']);
@endphp

<button {{ $triggerAttributes }}>{{ $slot }}</button>
