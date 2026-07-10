<button
    {{ $attributes->merge([
        'type' => 'button',
        'data-slot' => 'sidebar-menu-action',
        'data-sidebar' => 'menu-action',
        'data-show-on-hover' => $showOnHover ? 'true' : null,
    ]) }}
>{{ $slot }}</button>
