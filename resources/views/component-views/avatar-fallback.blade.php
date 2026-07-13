@php
    $hasSlot = trim($slot->toHtml()) !== '';
@endphp

<span {{ $attributes->merge(['data-slot' => 'avatar-fallback']) }}>{{ $hasSlot ? $slot : $fallbackText }}</span>
