@php
    $dropdownAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'dropdown',
        'data-controller' => 'dropdown',
        'data-dropdown-open-value' => $open ? 'true' : null,
        'data-dropdown-close-on-select-value' => $closeOnSelect ? null : 'false',
    ], $attributes, $stimulus, protectedPrefixes: ['data-dropdown-']);
@endphp

<div
    {{ $dropdownAttributes }}
>
    {{ $slot }}
</div>
