<div
    {{ $attributes->except(['data-overlay-return-region', 'data-overlay-return-src', 'data-overlay-return-fields'])->merge([
        'data-slot' => $slotName,
        'data-overlay-return-region' => $name,
        'data-overlay-return-src' => $src,
        'data-overlay-return-fields' => $fields === [] ? null : implode(' ', $fields),
    ]) }}
>{{ $slot }}</div>
