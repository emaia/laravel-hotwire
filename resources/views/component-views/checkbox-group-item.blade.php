@aware([
    'checkboxGroupContext' => false,
    'checkboxGroupName' => null,
    'checkboxGroupId' => null,
    'checkboxGroupErrorKey' => null,
    'checkboxGroupSelected' => [],
    'checkboxGroupOld' => true,
    'checkboxGroupSelectAll' => false,
    'checkboxGroupDisabled' => false,
    'checkboxGroupAutoSubmit' => false,
    'checkboxGroupAutoSubmitDelay' => null,
    'fieldName' => null,
    'fieldId' => null,
    'fieldErrorKey' => null,
])

@php
    if (! $checkboxGroupContext) {
        throw new InvalidArgumentException('Checkbox Group item must be rendered inside a Checkbox Group root. If a root is present, check for an intermediate component declaring a checkboxGroupContext prop, which shadows the root context.');
    }

    extract($compute(
        $checkboxGroupItemName ?? $checkboxGroupName ?? $fieldName,
        $checkboxGroupItemId ?? $checkboxGroupId ?? $fieldId,
        $checkboxGroupItemErrorKey ?? $checkboxGroupErrorKey ?? $fieldErrorKey,
        $checkboxGroupSelected,
        $checkboxGroupOld,
        $checkboxGroupSelectAll,
        $checkboxGroupDisabled,
        $checkboxGroupAutoSubmit,
        $checkboxGroupAutoSubmitDelay,
        $errors,
        $attributes,
    ));
@endphp

<label
    data-slot="checkbox-group-item"
    {{ trim($checkboxGroupItemLabelClass) !== '' ? $attributes->merge(['class' => $checkboxGroupItemLabelClass]) : $attributes->except('class') }}
>
    <input
        data-slot="checkbox-group-input"
        data-checkable="true"
        type="checkbox"
        @if (filled($checkboxGroupItemClass)) class="{{ $checkboxGroupItemClass }}" @endif
        @if ($name) name="{{ $name }}" @endif
        value="{{ $checkboxGroupItemValue }}"
        @if ($resolvedId) id="{{ $resolvedId }}" @endif
        @if ($errorId) aria-describedby="{{ $errorId }}" @endif
        @if ($hasErrors) aria-invalid="true" data-invalid @endif
        @if ($isDisabled) disabled @endif
        @if ($selectAllTarget) data-checkbox-select-all-target="{{ $selectAllTarget }}" @endif
        @if ($elementAction) data-action="{!! $elementAction !!}" @endif
        @if ($autoSubmitDelayParam !== null) data-auto-submit-delay-param="{{ $autoSubmitDelayParam }}" @endif
        @if ($isChecked) checked @endif
    />

    <span data-slot="checkbox-group-item-content">{{ $slot }}</span>
</label>
