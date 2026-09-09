@php $hasContent = trim((string) $slot) !== ''; @endphp

<div data-slot="{{ $slotName }}" data-content="{{ $hasContent ? 'true' : 'false' }}" {{ $attributes }}>
    <span data-slot="{{ $lineSlotName }}" aria-hidden="true"></span>

    @if ($hasContent)
        <span data-slot="{{ $contentSlotName }}">{{ $slot }}</span>
    @endif
</div>
