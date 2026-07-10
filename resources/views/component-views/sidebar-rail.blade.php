@aware(['identifier' => 'sidebar'])

<button
    {{ $attributes->merge([
        'type' => 'button',
        'data-slot' => 'sidebar-rail',
        'data-sidebar' => 'rail',
        'data-action' => "click->{$identifier}#toggle",
        'aria-label' => $label,
        'title' => $label,
        'tabindex' => '-1',
    ]) }}
></button>
