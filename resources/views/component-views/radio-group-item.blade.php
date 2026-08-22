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
    'radioGroupFieldContext' => null,
])

@php
    if (! $radioGroupContext) {
        throw new InvalidArgumentException('Radio Group item must be rendered inside a Radio Group root. If the item is passed into the slot of a wrapper component, move it inside the Radio Group root itself: slot content renders before the view of the wrapper, so the root is not on the stack yet. Otherwise check for an intermediate component declaring a radioGroupContext prop, which shadows the root context.');
    }

    $ownerName = $radioGroupName ?? $fieldName;
    $ownerId = $radioGroupId ?? $fieldId;
    $ownerErrorKey = \Emaia\LaravelHotwire\Support\FieldKey::resolveErrorKey($radioGroupErrorKey ?? null, $radioGroupName ?? null, $fieldErrorKey, $fieldName);
    $explicitName = $radioGroupItemName ?? null;
    $resolvedName = $explicitName ?? $ownerName;
    $resolvedId = \Emaia\LaravelHotwire\Support\FieldKey::resolveId($radioGroupItemId ?? null, $explicitName, $ownerId, $ownerName);
    $resolvedErrorKey = \Emaia\LaravelHotwire\Support\FieldKey::resolveErrorKey($radioGroupItemErrorKey ?? null, $explicitName, $ownerErrorKey, $ownerName);

    extract($compute(
        $resolvedName,
        $resolvedId,
        $resolvedErrorKey,
        $radioGroupSelected,
        $radioGroupOld,
        $radioGroupDisabled,
        $radioGroupAutoSubmit,
        $radioGroupAutoSubmitDelay,
        $errors,
        $attributes,
    ));
    $errorReference = $radioGroupFieldContext instanceof \Emaia\LaravelHotwire\Support\FieldContext
        ? $radioGroupFieldContext->errorReference($errorId, $name, $resolvedErrorKey)
        : null;
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
        @if ($errorReference) aria-describedby="{{ $errorReference }}" @endif
        @if ($hasErrors) aria-invalid="true" data-invalid @endif
        @if ($isDisabled) disabled @endif
        @if ($elementAction) data-action="{!! $elementAction !!}" @endif
        @if ($autoSubmitDelayParam !== null) data-auto-submit-delay-param="{{ $autoSubmitDelayParam }}" @endif
        @if ($isChecked) checked @endif
    />

    <span data-slot="radio-group-item-content">{{ $slot }}</span>
</label>
