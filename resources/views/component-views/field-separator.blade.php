@php $hasContent = trim((string) $slot) !== ''; @endphp

<div data-slot="field-separator" data-content="{{ $hasContent ? 'true' : 'false' }}" {{ $attributes }}>
    <span data-slot="field-separator-line" aria-hidden="true"></span>

    @if ($hasContent)
        <span data-slot="field-separator-content">{{ $slot }}</span>
    @endif
</div>
