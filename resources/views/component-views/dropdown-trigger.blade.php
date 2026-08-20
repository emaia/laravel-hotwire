@aware(['dropdownId' => null, 'dropdownOpen' => false])

@php
    if ($dropdownId === null && ! $dropdownTriggerStandalone) {
        throw new InvalidArgumentException('Dropdown trigger must be rendered inside a Dropdown root.');
    }

    $controls = $dropdownTriggerStandalone
        ? $attributes->get('aria-controls')
        : $dropdownId;

    if ($controls === null || $controls === '') {
        throw new InvalidArgumentException('Standalone Dropdown trigger requires an aria-controls attribute.');
    }

    $isOpen = $dropdownTriggerStandalone ? false : $dropdownOpen;
    $state = $isOpen ? 'open' : 'closed';
    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-dropdown-target' => $dropdownTriggerStandalone ? null : 'trigger',
        'data-action' => 'dropdown#toggle',
        'aria-haspopup' => 'true',
        'aria-expanded' => $isOpen ? 'true' : 'false',
        'aria-controls' => $controls,
        'data-dropdown-state' => $state,
    ], $attributes, except: ['data-dropdown-target', 'aria-haspopup', 'aria-expanded', 'aria-controls'], protectedPrefixes: ['data-dropdown-']);
@endphp

@if ($dropdownTriggerAsChild)
    {!! \Emaia\LaravelHotwire\Support\SlotAttributes::mergeIntoFirstElement($slot, $triggerAttributes) !!}
@else
    <button {{ $triggerAttributes->merge(['type' => 'button', 'data-slot' => 'dropdown-trigger']) }}>{{ $slot }}</button>
@endif
