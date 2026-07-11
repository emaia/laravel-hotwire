<div
    {{ $attributes->merge([
        'data-slot' => 'sidebar-menu-skeleton',
        'data-sidebar' => 'menu-skeleton',
        'style' => "--skeleton-width: {$width}",
    ]) }}
>
    @if ($showIcon)
        <div data-slot="sidebar-menu-skeleton-icon" data-sidebar="menu-skeleton-icon"></div>
    @endif

    <div data-slot="sidebar-menu-skeleton-text" data-sidebar="menu-skeleton-text"></div>
</div>
