<img
    {{ $attributes->merge([
        'src' => $src,
        'alt' => $alt ?? '',
        'data-slot' => 'avatar-image',
    ]) }}
>
