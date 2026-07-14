@aware(['formattedPercentage' => null])

@php
    $hasSlot = trim($slot->toHtml()) !== '';
@endphp

<span {{ $attributes->merge(['data-slot' => 'progress-value']) }}>{{ $hasSlot ? $slot : ($formattedPercentage !== null ? "{$formattedPercentage}%" : '') }}</span>
