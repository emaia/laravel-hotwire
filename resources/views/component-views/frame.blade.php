@php
    extract($compute($attributes));
@endphp

<turbo-frame {{ $frameAttributes }}>{{ $slot }}</turbo-frame>
