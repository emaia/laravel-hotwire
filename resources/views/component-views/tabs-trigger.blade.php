@aware(['tabsId' => null, 'active' => null, 'identifier' => 'tabs'])

@php
    extract($compute($tabsId, $active, $identifier, $attributes));
@endphp

<button
    {{ $triggerAttributes }}
>{{ $slot }}</button>
