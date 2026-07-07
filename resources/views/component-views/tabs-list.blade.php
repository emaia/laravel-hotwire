@aware(['identifier' => 'tabs', 'tabsOrientation' => 'horizontal'])

@php
    extract($compute($identifier, $tabsOrientation, $attributes));
@endphp

<div
    {{ $listAttributes }}
>{{ $slot }}</div>
