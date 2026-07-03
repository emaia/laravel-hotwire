@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php
    extract($compute($name, $id, $errorKey, $required, $errors, $attributes));

@endphp

@if ($clearable)
<span data-slot="input-wrapper" data-clearable="true" @if ($wrapperClass !== '') class="{{ $wrapperClass }}" @endif data-controller="clear-input">
@endif

<input
    data-slot="input"
    data-checkable="{{ $isCheckable ? 'true' : 'false' }}"
    type="{{ $type }}"
    id="{{ $resolvedId }}"
    @if ($name) name="{{ $name }}" @endif
    @if ($isCheckable)
        @if ($resolvedValue !== null) value="{{ $resolvedValue }}" @endif
        @if ($isChecked) checked @endif
    @else
        @if ($resolvedValue !== null) value="{{ $resolvedValue }}" @endif
    @endif
    aria-describedby="{{ $errorId }}"
    @if ($hasErrors) aria-invalid="true" data-invalid @endif
    @if ($isRequired) aria-required="true" required @endif
    @if ($elementController !== '') data-controller="{{ $elementController }}" @endif
    @if ($mask !== null) data-input-mask-mask-value="{{ $resolvedMask }}" @endif
    @if ($clearable) data-clear-input-target="input" @endif
    {{ $attributes->merge([
            'class' => $class ?: null,
        ])->whereDoesntStartWith(array_merge(['data-controller'], $internalPrefixes))->except(['required', 'checked']) }}
/>

@if ($clearable)
    <button
        type="button"
        class="hidden"
        data-slot="clear-input-button"
        data-clear-input-target="clearButton"
        tabindex="0"
        aria-label="Clear"
    >
        <hw:icon name="circle-x" />
    </button>
</span>
@endif
