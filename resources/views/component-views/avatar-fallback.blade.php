@php
    $hasSlot = trim($slot->toHtml()) !== '';
@endphp

<span {{ $attributes->merge(['data-slot' => $slotName]) }}>{{ $hasSlot ? $slot : $fallbackText }}</span>
