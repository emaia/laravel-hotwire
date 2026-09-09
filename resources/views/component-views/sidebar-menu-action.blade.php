<button
    {{ $attributes->merge([
        'type' => 'button',
        'data-slot' => $slotName,
        'data-sidebar' => 'menu-action',
        'data-show-on-hover' => $showOnHover ? 'true' : null,
    ]) }}
>{{ $slot }}</button>
