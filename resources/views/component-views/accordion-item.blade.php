@aware(['identifier' => 'accordion', 'accordionValue' => []])

@php
    extract($compute($identifier, $accordionValue, $attributes));
@endphp

<details
    {{ $itemAttributes }}
>{{ $slot }}</details>
