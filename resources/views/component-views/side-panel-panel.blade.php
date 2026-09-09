@aware(['sidePanelIdentifier' => 'side-panel', 'sidePanelPanelId' => null, 'sidePanelState' => 'expanded'])

@php
    extract($compute($sidePanelPanelId, $sidePanelIdentifier, $sidePanelState, $attributes));
@endphp

<aside {{ $panelAttributes }}>
    <div data-slot="{{ $panelContentSlotName }}">{{ $slot }}</div>
</aside>
