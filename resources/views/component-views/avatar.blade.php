<span
    {{ $attributes->merge([
        'data-slot' => 'avatar',
        'data-size' => $size,
        'data-shape' => $shape,
    ]) }}
>
    @if ($src !== null && $src !== '')
        <x-hw::avatar.image :src="$src" :alt="$imageAlt" />
    @endif

    @if ($fallbackText !== null && $fallbackText !== '')
        <x-hw::avatar.fallback>{{ $fallbackText }}</x-hw::avatar.fallback>
    @endif

    {{ $slot }}
</span>
