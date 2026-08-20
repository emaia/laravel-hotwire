@aware(['popoverId' => null, 'popoverOpen' => false])

@php
    if ($popoverId === null && ! $popoverTriggerStandalone) {
        throw new InvalidArgumentException('Popover trigger must be rendered inside a Popover root.');
    }

    $controls = $popoverTriggerStandalone
        ? $attributes->get('aria-controls')
        : $popoverId;

    if ($controls === null || $controls === '') {
        throw new InvalidArgumentException('Standalone Popover trigger requires an aria-controls attribute.');
    }

    $isOpen = $popoverTriggerStandalone ? false : $popoverOpen;

    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => 'button',
        'data-slot' => 'popover-trigger',
        'data-popover-target' => $popoverTriggerStandalone ? null : 'trigger',
        'data-action' => 'popover#toggle',
        'aria-haspopup' => 'dialog',
        'aria-expanded' => $isOpen ? 'true' : 'false',
        'aria-controls' => $controls,
        'data-popover-state' => $isOpen ? 'open' : 'closed',
    ], $attributes, except: ['type', 'data-slot', 'aria-haspopup', 'aria-expanded', 'aria-controls'], protectedPrefixes: ['data-popover-']);
@endphp

<button {{ $triggerAttributes }}>{{ $slot }}</button>
