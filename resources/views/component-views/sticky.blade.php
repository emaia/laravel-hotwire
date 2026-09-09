<{{ $as }}
    {{ $attributes->merge([
        'data-slot' => $slotName,
        'data-side' => $side,
        'data-surface' => $surface ? 'true' : 'false',
        'style' => "--sticky-offset: {$offset};",
    ]) }}
>{{ $slot }}</{{ $as }}>
