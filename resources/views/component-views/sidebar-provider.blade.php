@php
    extract($compute($attributes));
@endphp

<div {{ $providerAttributes }}>
    {{ $slot }}
</div>
