@aware(['sidebarIdentifier' => 'sidebar'])

<button
    {{ $attributes->merge([
        'type' => 'button',
        'data-slot' => $slotName,
        'data-sidebar' => 'rail',
        'data-action' => "click->{$sidebarIdentifier}#toggle",
        'aria-label' => $label,
        'title' => $label,
        'tabindex' => '-1',
    ]) }}
></button>
