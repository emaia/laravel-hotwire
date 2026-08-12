@php
    extract($compute($attributes));
@endphp

<div {{ $sidePanelAttributes }}>{{ $slot }}</div>
