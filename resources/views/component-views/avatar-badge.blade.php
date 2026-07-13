<span
    {{ $attributes->merge([
        'data-slot' => 'avatar-badge',
        'data-position' => $position,
    ]) }}
>{{ $slot }}</span>
