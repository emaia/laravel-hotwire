@aware(['tabsId' => null, 'active' => null, 'tabsIdentifier' => 'tabs'])

@php
    extract($compute($tabsId, $active, $tabsIdentifier, $attributes));
@endphp

<button
    {{ $triggerAttributes }}
>{{ $slot }}</button>
