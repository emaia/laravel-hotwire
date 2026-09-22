@php
    $userStyle = trim((string) $attributes->get('style'));
    $style = $userStyle !== '' ? rtrim($userStyle, ';').';' : null;
@endphp

<{{ $as }}
    {{ $attributes->except(['as', 'style', 'data-slot', 'data-reveal-item'])->merge([
        'data-slot' => $slotName,
        'data-reveal-item' => true,
        'style' => $style,
    ]) }}
>{{ $slot }}</{{ $as }}>
