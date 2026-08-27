@aware([
    'overlayLabelOwnerContext' => null,
])

@php
    $overlaySlotId = $overlayLabelOwnerContext?->register($slotName, $attributes->get('id'));
    if ($overlaySlotId !== null && ($attributes->get('id') === null || $attributes->get('id') === '')) {
        $attributes = $attributes->except('id')->merge(['id' => $overlaySlotId]);
    }
@endphp

<{{ $tag }} data-slot="{{ $slotName }}" @isset($variant) data-variant="{{ $variant }}" @endisset {{ $attributes }}>{{ $slot }}</{{ $tag }}>
