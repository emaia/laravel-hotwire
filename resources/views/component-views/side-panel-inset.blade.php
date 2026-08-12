@php
    extract($compute($attributes));
@endphp

<main {{ $insetAttributes }}>{{ $slot }}</main>
