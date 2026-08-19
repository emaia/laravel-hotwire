@aware(['tabsId' => null, 'tabsActive' => null, 'tabsIdentifier' => 'tabs'])

@php
    extract($compute($tabsId, $tabsActive, $tabsIdentifier, $attributes));
@endphp

<div
    {{ $panelAttributes }}
>{{ $slot }}</div>
