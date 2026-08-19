@aware(['drawerId' => null])

@php
    if ($drawerId === null) {
        throw new InvalidArgumentException('Drawer trigger must be rendered inside a Drawer root.');
    }
@endphp

<button {{ $attributes->merge(['type' => 'button', 'data-slot' => 'drawer-trigger', 'data-drawer-target' => 'trigger', 'data-action' => 'click->drawer#toggle']) }}>{{ $slot }}</button>
