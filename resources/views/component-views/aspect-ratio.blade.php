@php
    $userStyle = trim((string) $attributes->get('style'));
    $style = "--ratio: {$resolvedRatio};".($userStyle !== '' ? " {$userStyle}" : '');
@endphp

<div
    {{ $attributes->except('style')->merge([
        'data-slot' => 'aspect-ratio',
        'style' => $style,
    ]) }}
>{{ $slot }}</div>
