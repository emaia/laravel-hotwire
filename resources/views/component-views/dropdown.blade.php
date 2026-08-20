@php
    $dropdownAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'dropdown',
        'data-controller' => 'dropdown',
        'data-dropdown-open-value' => $dropdownOpen ? 'true' : null,
        'data-dropdown-close-on-select-value' => $dropdownCloseOnSelect ? null : 'false',
    ], $attributes, $dropdownStimulus, protectedPrefixes: ['data-dropdown-']);
@endphp

<div
    {{ $dropdownAttributes }}
>
    {{ $slot }}
</div>
