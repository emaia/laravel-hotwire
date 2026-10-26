<div
    {{ $attributes->except('data-overlay-return-region')->merge([
        'data-slot' => $slotName,
        'data-overlay-return-region' => $name,
    ]) }}
>{{ $slot }}</div>
