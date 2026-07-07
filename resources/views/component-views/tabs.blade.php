@php
    extract($compute($attributes));
@endphp

<div
    {{ $tabsAttributes }}
>{{ $slot }}</div>
