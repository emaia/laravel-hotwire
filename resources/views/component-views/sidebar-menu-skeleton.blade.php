<div
    {{ $attributes->merge([
        'data-slot' => $slotName,
        'data-sidebar' => 'menu-skeleton',
        'style' => "--skeleton-width: {$width}",
    ]) }}
>
    @if ($showIcon)
        <div data-slot="{{ $iconSlotName }}" data-sidebar="menu-skeleton-icon"></div>
    @endif

    <div data-slot="{{ $textSlotName }}" data-sidebar="menu-skeleton-text"></div>
</div>
