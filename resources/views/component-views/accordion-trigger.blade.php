@php
    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => $slotName,
    ], $attributes, $stimulus);
@endphp

<summary
    {{ $triggerAttributes }}
>
    {{ $slot }}

    @if ($icon)
        <x-hw::icon name="chevron-down" data-slot="{{ $iconSlotName }}" aria-hidden="true" />
    @endif
</summary>
