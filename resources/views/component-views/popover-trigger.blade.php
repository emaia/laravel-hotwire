@aware(['id' => '', 'open' => false])

@php
    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => 'button',
        'data-slot' => 'popover-trigger',
        'data-popover-target' => 'trigger',
        'data-action' => 'popover#toggle',
        'aria-haspopup' => 'dialog',
        'aria-expanded' => $open ? 'true' : 'false',
        'aria-controls' => $id,
        'data-popover-state' => $open ? 'open' : 'closed',
    ], $attributes, except: ['type', 'data-slot', 'aria-haspopup', 'aria-expanded', 'aria-controls'], protectedPrefixes: ['data-popover-']);
@endphp

<button {{ $triggerAttributes }}>{{ $slot }}</button>
