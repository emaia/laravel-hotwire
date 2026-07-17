@aware(['id' => '', 'open' => false])

@php
    $state = $open ? 'open' : 'closed';
    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-dropdown-target' => 'trigger',
        'data-action' => 'dropdown#toggle',
        'aria-haspopup' => 'true',
        'aria-expanded' => $open ? 'true' : 'false',
        'aria-controls' => $id,
        'data-state' => $state,
    ], $attributes, except: ['data-dropdown-target', 'aria-haspopup', 'aria-expanded', 'aria-controls', 'data-state'], protectedPrefixes: ['data-dropdown-']);
@endphp

@if ($asChild)
    {!! \Emaia\LaravelHotwire\Support\SlotAttributes::mergeIntoFirstElement($slot, $triggerAttributes) !!}
@else
    <button {{ $triggerAttributes->merge(['type' => 'button', 'data-slot' => 'dropdown-trigger']) }}>{{ $slot }}</button>
@endif
