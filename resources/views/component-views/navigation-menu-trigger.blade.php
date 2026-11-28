@aware(['navigationMenuId' => null])

@php
    if ($navigationMenuId === null || $value === '') {
        throw new InvalidArgumentException('Navigation Menu triggers require a non-empty value and a Navigation Menu root.');
    }

    $key = rawurlencode($value);
    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'id' => "{$navigationMenuId}-trigger-{$key}",
        'type' => 'button',
        'disabled' => $disabled,
        'data-slot' => $slotName,
        'data-state' => 'closed',
        'aria-expanded' => 'false',
        'aria-controls' => "{$navigationMenuId}-content-{$key}",
        'data-navigation-menu-target' => 'trigger',
        'data-action' => 'pointerenter->navigation-menu#hoverTrigger pointerleave->navigation-menu#cancelOpen navigation-menu#toggle',
    ], $attributes, except: ['id', 'type', 'data-slot', 'data-state', 'aria-expanded', 'aria-controls'],
        protectedPrefixes: ['data-navigation-menu-']);
@endphp

<button {{ $triggerAttributes }}>
    {{ $slot }}
    @if ($chevron)
        <x-hw::icon name="chevron-down" data-slot="{{ $iconSlotName }}" aria-hidden="true" />
    @endif
</button>
