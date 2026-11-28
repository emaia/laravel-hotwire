@aware(['navigationMenuId' => null])

@php
    if ($navigationMenuId === null || $value === '') {
        throw new InvalidArgumentException('Navigation Menu content requires a non-empty value and a Navigation Menu root.');
    }

    $key = rawurlencode($value);
    $contentId = "{$navigationMenuId}-content-{$key}";
    $contentAttributes = $attributes
        ->except(['id', 'data-slot', 'data-state', 'data-motion', 'data-navigation-menu-target', 'role', 'aria-labelledby', 'hidden', 'inert'])
        ->merge([
            'id' => $contentId,
            'data-slot' => $slotName,
            'data-state' => 'closed',
            'data-navigation-menu-target' => 'content',
            'role' => 'group',
            'aria-labelledby' => "{$navigationMenuId}-trigger-{$key}",
            'hidden' => true,
            'inert' => true,
        ]);
@endphp

<div {{ $contentAttributes }}>
    @if ($src !== null)
        <turbo-frame id="{{ $frame ?? $contentId.'-frame' }}" target="_top" data-navigation-menu-src="{{ $src }}">
            {{ $slot }}
        </turbo-frame>
    @else
        {{ $slot }}
    @endif
</div>
