@aware([
    'name' => null,
    'id' => null,
    'errorKey' => null,
    'selected' => null,
    'old' => true,
    'radioGroupDisabled' => false,
    'autoSubmit' => false,
    'autoSubmitDelay' => null,
])

@php
    extract($compute($name, $id, $errorKey, $selected, $old, $radioGroupDisabled, $autoSubmit, $autoSubmitDelay, $errors, $attributes));
@endphp

<label
    data-slot="radio-group-item"
    {{ trim($labelClass) !== '' ? $attributes->merge(['class' => $labelClass]) : $attributes->except('class') }}
>
    <input
        data-slot="radio-group-input"
        data-checkable="true"
        type="radio"
        @if (filled($class)) class="{{ $class }}" @endif
        @if ($name) name="{{ $name }}" @endif
        value="{{ $value }}"
        @if ($resolvedId) id="{{ $resolvedId }}" @endif
        @if ($errorId) aria-describedby="{{ $errorId }}" @endif
        @if ($hasErrors) aria-invalid="true" data-invalid @endif
        @if ($isDisabled) disabled @endif
        @if ($elementAction) data-action="{!! $elementAction !!}" @endif
        @if ($autoSubmitDelayParam !== null) data-auto-submit-delay-param="{{ $autoSubmitDelayParam }}" @endif
        @if ($isChecked) checked @endif
    />

    <span data-slot="radio-group-item-content">{{ $slot }}</span>
</label>
