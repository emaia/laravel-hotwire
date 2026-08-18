@aware(['sidePanelIdentifier' => 'side-panel', 'panelId' => null, 'sidePanelState' => 'expanded'])

@php
    extract($compute($panelId, $sidePanelIdentifier, $sidePanelState, $attributes));
@endphp

<aside {{ $panelAttributes }}>
    <div data-slot="side-panel-panel-content">{{ $slot }}</div>
</aside>
