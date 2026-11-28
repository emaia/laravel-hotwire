<div {{ $attributes
    ->except(['data-slot', 'data-state', 'data-navigation-menu-target', 'data-positioned', 'hidden', 'inert'])
    ->merge([
        'data-slot' => $slotName,
        'data-state' => 'closed',
        'data-navigation-menu-target' => 'viewport',
        'hidden' => true,
        'inert' => true,
    ]) }}>{{ $slot }}</div>
