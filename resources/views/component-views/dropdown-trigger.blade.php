@aware(['dropdownId' => null, 'dropdownOpen' => false])

@php
    $controls = $dropdownId ?? $attributes->get('aria-controls');

    if ($controls === null || $controls === '') {
        throw new InvalidArgumentException('Dropdown trigger must be rendered inside a Dropdown root.');
    }

    $state = $dropdownOpen ? 'open' : 'closed';
    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-dropdown-target' => 'trigger',
        'data-action' => 'dropdown#toggle',
        'aria-haspopup' => 'true',
        'aria-expanded' => $dropdownOpen ? 'true' : 'false',
        'aria-controls' => $controls,
        'data-dropdown-state' => $state,
    ], $attributes, except: ['data-dropdown-target', 'aria-haspopup', 'aria-expanded', 'aria-controls'], protectedPrefixes: ['data-dropdown-']);
@endphp

@if ($asChild)
    {!! \Emaia\LaravelHotwire\Support\SlotAttributes::mergeIntoFirstElement($slot, $triggerAttributes) !!}
@else
    <button {{ $triggerAttributes->merge(['type' => 'button', 'data-slot' => 'dropdown-trigger']) }}>{{ $slot }}</button>
@endif
