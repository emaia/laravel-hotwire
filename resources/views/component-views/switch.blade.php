@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null, 'fieldRequired' => false, 'fieldControlContext' => null])

@php
    $explicitName = $name ?? null;
    $id = \Emaia\LaravelHotwire\Support\FieldKey::resolveId($id ?? null, $explicitName, $fieldId, $fieldName);
    $errorKey = \Emaia\LaravelHotwire\Support\FieldKey::resolveErrorKey($errorKey ?? null, $explicitName, $fieldErrorKey, $fieldName);
    $name = $explicitName ?? $fieldName;
    extract($compute($name, $id, $errorKey, $fieldRequired ?? false, $errors, $attributes));

    $errorReference = null;
    if ($fieldControlContext instanceof \Emaia\LaravelHotwire\Support\FieldContext && $resolvedId) {
        $fieldControlContext->registerControl($resolvedId, $name, 'checkbox', $errorId, $resolvedErrorKey, $isRequired);
        $errorReference = $fieldControlContext->errorReference($errorId, $name, $resolvedErrorKey);
    }

    $switchAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'switch',
        'data-checkable' => 'true',
        'data-size' => $size,
        'type' => 'checkbox',
        'role' => 'switch',
        'id' => $resolvedId,
        'name' => $name ?: null,
        'value' => $value,
        'checked' => $isChecked ? true : null,
        'aria-describedby' => $errorReference,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'data-invalid' => $hasErrors ? true : null,
        'data-disabled' => $isDisabled ? 'true' : null,
        'aria-required' => $isRequired ? 'true' : null,
        'required' => $isRequired ? true : null,
        'data-action' => $elementAction ?: null,
        'data-auto-submit-delay-param' => $autoSubmitDelayParam,
        'class' => $class ?: null,
    ], $attributes, $stimulus, except: ['checked', 'required', 'auto-submit', 'auto-submit-delay', 'unchecked-value', 'size'], protectedPrefixes: $internalPrefixes);
@endphp

@if ($renderUncheckedValue)
    <input type="hidden" name="{{ $name }}" value="{{ $uncheckedValue }}" @if ($hiddenDisabled) disabled @endif />
@endif

<input
    {{ $switchAttributes }}
/>
