@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php extract($compute($name, $id, $errorKey, $required, $errors, $attributes)) @endphp

@if ($clearable)
<span @class(['inline-flex flex-col justify-center items-center relative', $wrapperClass]) data-controller="clear-input">
@endif

<input
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
    {{ $attributes->merge(
            filled($class)
                ? ['class' => $class]
                : []
        )->whereDoesntStartWith(array_merge(['data-controller'], $internalPrefixes))->except(['required', 'checked']) }}
/>

@if ($clearable)
    <button
        type="button"
        data-clear-input-target="clearButton"
        class="clear-input-button absolute right-1.5 hidden items-center"
        tabindex="0"
        aria-label="Clear"
    >
        <x-hwc::icon name="circle-x" class="w-4 h-4" />
    </button>
</span>
@endif
