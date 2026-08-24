@aware(['progressPercentage' => null])

@php
    $hasSlot = trim($slot->toHtml()) !== '';

    if (! $hasSlot && $progressPercentage === null && ! $progressValueStandalone) {
        throw new InvalidArgumentException('Progress value without rendered content must be inside a Progress root. If standalone content may render empty, pass the standalone prop. If the value is passed into the slot of a wrapper component, move it inside the Progress root itself: slot content renders before the view of the wrapper, so the root is not on the stack yet.');
    }

    $content = $hasSlot || $progressValueStandalone ? $slot : "{$progressPercentage}%";
@endphp

<span {{ $attributes->except('data-slot')->merge(['data-slot' => 'progress-value']) }}>{{ $content }}</span>
