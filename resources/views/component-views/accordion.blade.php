@php
    extract($compute($attributes));
@endphp

<section
    {{ $accordionAttributes }}
>{{ $slot }}</section>
