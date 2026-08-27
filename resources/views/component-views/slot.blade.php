@aware([
    'modalOverlayLabelContext' => null,
    'sheetOverlayLabelContext' => null,
    'drawerOverlayLabelContext' => null,
    'alertDialogOverlayLabelContext' => null,
])

@php
    $overlaySlotId = $modalOverlayLabelContext?->register($slotName, $attributes->get('id'))
        ?? $sheetOverlayLabelContext?->register($slotName, $attributes->get('id'))
        ?? $drawerOverlayLabelContext?->register($slotName, $attributes->get('id'))
        ?? $alertDialogOverlayLabelContext?->register($slotName, $attributes->get('id'));
    if ($overlaySlotId !== null && ($attributes->get('id') === null || $attributes->get('id') === '')) {
        $attributes = $attributes->except('id')->merge(['id' => $overlaySlotId]);
    }
@endphp

<{{ $tag }} data-slot="{{ $slotName }}" @isset($variant) data-variant="{{ $variant }}" @endisset {{ $attributes }}>{{ $slot }}</{{ $tag }}>
