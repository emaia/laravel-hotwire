<img
    {{ $attributes->merge([
        'src' => $src,
        'alt' => $alt ?? '',
        'data-slot' => $slotName,
    ]) }}
>
