<span
    {{ $attributes->merge([
        'data-slot' => $slotName,
        'data-position' => $position,
    ]) }}
>{{ $slot }}</span>
