@aware(['identifier' => 'side-panel', 'panelId' => null, 'sidePanelState' => 'expanded'])

@php
    extract($compute($panelId, $identifier, $sidePanelState, $attributes));
@endphp

<aside {{ $panelAttributes }}>
    <div data-slot="side-panel-panel-content">{{ $slot }}</div>
</aside>
