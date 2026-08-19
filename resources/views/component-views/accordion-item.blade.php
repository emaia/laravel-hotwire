@aware(['accordionIdentifier' => null, 'accordionValue' => []])

@php
    extract($compute($accordionIdentifier, $accordionValue, $attributes));
@endphp

<details
    {{ $itemAttributes }}
>{{ $slot }}</details>
