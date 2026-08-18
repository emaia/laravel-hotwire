@aware(['sidePanelIdentifier' => 'side-panel', 'panelId' => null, 'sidePanelState' => 'expanded'])

@php
    extract($compute($panelId, $sidePanelIdentifier, $sidePanelState, $attributes));
@endphp

<button {{ $triggerAttributes }}>
    <x-hw::icon name="chevron-left" data-slot="side-panel-trigger-icon" aria-hidden="true" />
    <span hidden>{{ $label }}</span>
</button>
