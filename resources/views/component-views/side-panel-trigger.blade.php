@aware(['sidePanelIdentifier' => 'side-panel', 'sidePanelPanelId' => null, 'sidePanelState' => 'expanded'])

@php
    extract($compute($sidePanelPanelId, $sidePanelIdentifier, $sidePanelState, $attributes));
@endphp

<button {{ $triggerAttributes }}>
    <x-hw::icon name="chevron-left" data-slot="side-panel-trigger-icon" aria-hidden="true" />
    <span hidden>{{ $label }}</span>
</button>
