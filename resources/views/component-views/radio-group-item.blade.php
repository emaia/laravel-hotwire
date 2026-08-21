@aware([
    'radioGroupContext' => false,
    'radioGroupName' => null,
    'radioGroupId' => null,
    'radioGroupErrorKey' => null,
    'radioGroupSelected' => null,
    'radioGroupOld' => true,
    'radioGroupDisabled' => false,
    'radioGroupAutoSubmit' => false,
    'radioGroupAutoSubmitDelay' => null,
    'fieldName' => null,
    'fieldId' => null,
    'fieldErrorKey' => null,
])

@php
    if (! $radioGroupContext) {
        throw new InvalidArgumentException('Radio Group item must be rendered inside a Radio Group root. If a root is present, check for an intermediate component declaring a radioGroupContext prop, which shadows the root context.');
    }

    extract($compute(
        $radioGroupItemName ?? $radioGroupName ?? $fieldName,
        $radioGroupItemId ?? $radioGroupId ?? $fieldId,
        $radioGroupItemErrorKey ?? $radioGroupErrorKey ?? $fieldErrorKey,
        $radioGroupSelected,
        $radioGroupOld,
        $radioGroupDisabled,
        $radioGroupAutoSubmit,
        $radioGroupAutoSubmitDelay,
        $errors,
        $attributes,
    ));
@endphp

<label
    data-slot="radio-group-item"
    {{ trim($radioGroupItemLabelClass) !== '' ? $attributes->merge(['class' => $radioGroupItemLabelClass]) : $attributes->except('class') }}
>
    <input
        data-slot="radio-group-input"
        data-checkable="true"
        type="radio"
        @if (filled($radioGroupItemClass)) class="{{ $radioGroupItemClass }}" @endif
        @if ($name) name="{{ $name }}" @endif
        value="{{ $radioGroupItemValue }}"
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
