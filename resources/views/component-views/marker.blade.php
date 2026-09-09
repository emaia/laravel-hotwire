<div
    {{ $attributes->merge([
        'data-slot' => $slotName,
        'data-variant' => $variant,
    ]) }}
>{{ $slot }}</div>
