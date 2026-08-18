@aware(['sidebarIdentifier' => 'sidebar'])

<button
    {{ $attributes->merge([
        'type' => 'button',
        'data-slot' => 'sidebar-trigger',
        'data-sidebar' => 'trigger',
        'data-action' => "click->{$sidebarIdentifier}#toggle",
        'aria-label' => $label,
    ]) }}
>
    <x-hw::icon name="panel-left" aria-hidden="true" />
    <span hidden>{{ $label }}</span>
</button>
