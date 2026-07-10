@php
    extract($compute($attributes));
@endphp

<div {{ $sheetAttributes }}>
    {{ $slot }}
</div>
