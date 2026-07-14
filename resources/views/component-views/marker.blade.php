<div
    {{ $attributes->merge([
        'data-slot' => 'marker',
        'data-variant' => $variant,
    ]) }}
>{{ $slot }}</div>
