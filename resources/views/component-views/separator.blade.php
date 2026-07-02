<div
    data-slot="{{ $slotName }}"
    data-orientation="{{ $orientation }}"
    role="separator"
    @if ($orientation === 'vertical') aria-orientation="vertical" @endif
    {{ $attributes }}
></div>
