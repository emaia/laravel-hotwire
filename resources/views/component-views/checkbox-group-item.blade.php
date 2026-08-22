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
    'checkboxGroupFieldContext' => null,
])

@php
    if (! $checkboxGroupContext) {
        throw new InvalidArgumentException('Checkbox Group item must be rendered inside a Checkbox Group root. If the item is passed into the slot of a wrapper component, move it inside the Checkbox Group root itself: slot content renders before the view of the wrapper, so the root is not on the stack yet. Otherwise check for an intermediate component declaring a checkboxGroupContext prop, which shadows the root context.');
    }

    $ownerName = $checkboxGroupName ?? $fieldName;
    $ownerId = $checkboxGroupId ?? $fieldId;
    $ownerErrorKey = \Emaia\LaravelHotwire\Support\FieldKey::resolveErrorKey($checkboxGroupErrorKey ?? null, $checkboxGroupName ?? null, $fieldErrorKey, $fieldName);
    $explicitName = $checkboxGroupItemName ?? null;
    $resolvedName = $explicitName ?? $ownerName;
    $resolvedId = \Emaia\LaravelHotwire\Support\FieldKey::resolveId($checkboxGroupItemId ?? null, $explicitName, $ownerId, $ownerName);
    $resolvedErrorKey = \Emaia\LaravelHotwire\Support\FieldKey::resolveErrorKey($checkboxGroupItemErrorKey ?? null, $explicitName, $ownerErrorKey, $ownerName);

    extract($compute(
        $resolvedName,
        $resolvedId,
        $resolvedErrorKey,
        $checkboxGroupSelected,
        $checkboxGroupOld,
        $checkboxGroupSelectAll,
        $checkboxGroupDisabled,
        $checkboxGroupAutoSubmit,
        $checkboxGroupAutoSubmitDelay,
        $errors,
        $attributes,
    ));
    $errorReference = $checkboxGroupFieldContext instanceof \Emaia\LaravelHotwire\Support\FieldContext
        ? $checkboxGroupFieldContext->errorReference($errorId, $name, $resolvedErrorKey)
        : null;
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
        @if ($errorReference) aria-describedby="{{ $errorReference }}" @endif
        @if ($hasErrors) aria-invalid="true" data-invalid @endif
        @if ($isDisabled) disabled @endif
        @if ($selectAllTarget) data-checkbox-select-all-target="{{ $selectAllTarget }}" @endif
        @if ($elementAction) data-action="{!! $elementAction !!}" @endif
        @if ($autoSubmitDelayParam !== null) data-auto-submit-delay-param="{{ $autoSubmitDelayParam }}" @endif
        @if ($isChecked) checked @endif
    />

    <span data-slot="checkbox-group-item-content">{{ $slot }}</span>
</label>
