@aware(['accordionIdentifier' => 'accordion', 'accordionValue' => []])

@php
    extract($compute($accordionIdentifier, $accordionValue, $attributes));
@endphp

<details
    {{ $itemAttributes }}
>{{ $slot }}</details>
