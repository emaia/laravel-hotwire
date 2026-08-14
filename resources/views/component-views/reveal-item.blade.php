@aware(['revealCounter' => null])

@php
    $index = $revealCounter?->index ?? 0;
    $revealOwner = $revealCounter !== null ? spl_object_id($revealCounter) : null;

    if ($revealCounter !== null) {
        $revealCounter->index++;
    }

    $userStyle = trim((string) $attributes->get('style'));
    $style = "--reveal-index: {$index};".($userStyle !== '' ? " {$userStyle}" : '');
@endphp

<{{ $as }}
    {{ $attributes->except(['as', 'style', 'data-slot', 'data-reveal-item', 'data-reveal-owner'])->merge([
        'data-slot' => 'reveal-item',
        'data-reveal-item' => true,
        'data-reveal-owner' => $revealOwner,
        'style' => $style,
    ]) }}
>{{ $slot }}</{{ $as }}>
