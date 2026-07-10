@php
    extract($compute($attributes));
@endphp

<div {{ $drawerAttributes }}>
    {{ $slot }}
</div>
