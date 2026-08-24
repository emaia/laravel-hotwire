@aware(['progressPercentage' => null])

@php
    $hasSlot = trim($slot->toHtml()) !== '';

    if (! $hasSlot && $progressPercentage === null) {
        throw new InvalidArgumentException('Progress value without explicit content must be rendered inside a Progress root. If the value is passed into the slot of a wrapper component, move it inside the Progress root itself: slot content renders before the view of the wrapper, so the root is not on the stack yet. Otherwise check for an intermediate component declaring a progressPercentage prop, which shadows the root context.');
    }
@endphp

<span {{ $attributes->merge(['data-slot' => 'progress-value']) }}>{{ $hasSlot ? $slot : "{$progressPercentage}%" }}</span>
