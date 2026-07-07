@aware(['tabsId' => null, 'active' => null, 'identifier' => 'tabs'])

@php
    extract($compute($tabsId, $active, $identifier, $attributes));
@endphp

<div
    {{ $panelAttributes }}
>{{ $slot }}</div>
