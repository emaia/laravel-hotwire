@aware(['tabsIdentifier' => 'tabs', 'tabsOrientation' => 'horizontal'])

@php
    extract($compute($tabsIdentifier, $tabsOrientation, $attributes));
@endphp

<div
    {{ $listAttributes }}
>{{ $slot }}</div>
