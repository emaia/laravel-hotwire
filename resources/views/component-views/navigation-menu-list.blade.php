<ul {{ $attributes->except(['data-slot', 'data-navigation-menu-target'])->merge([
    'data-slot' => $slotName,
    'data-navigation-menu-target' => 'list',
]) }}>{{ $slot }}</ul>
