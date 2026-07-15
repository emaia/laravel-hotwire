@php
    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'accordion-trigger',
    ], $attributes, $stimulus);
@endphp

<summary
    {{ $triggerAttributes }}
>
    {{ $slot }}

    @if ($icon)
        <x-hw::icon name="chevron-down" data-slot="accordion-trigger-icon" aria-hidden="true" />
    @endif
</summary>
