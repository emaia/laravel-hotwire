@aware(['revealContext' => null])

@php
    $hasRevealContext = $revealContext instanceof \Emaia\LaravelHotwire\Support\RevealContext;
    $index = $hasRevealContext ? $revealContext->nextIndex() : null;
    $revealOwner = $hasRevealContext ? $revealContext->owner() : null;

    $userStyle = trim((string) $attributes->get('style'));
    $style = collect([
        $index !== null ? "--reveal-index: {$index}" : null,
        $userStyle !== '' ? $userStyle : null,
    ])->filter()->implode('; ');
    $style = $style !== '' ? $style.';' : null;
@endphp

<{{ $as }}
    {{ $attributes->except(['as', 'style', 'data-slot', 'data-reveal-item', 'data-reveal-owner'])->merge([
        'data-slot' => $slotName,
        'data-reveal-item' => true,
        'data-reveal-owner' => $revealOwner,
        'style' => $style,
    ]) }}
>{{ $slot }}</{{ $as }}>
