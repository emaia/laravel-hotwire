@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null, 'fieldRequired' => false])

@php
    $name = $name ?? $fieldName;
    $id = $id ?? $fieldId;
    $errorKey = $errorKey ?? $fieldErrorKey;
    extract($compute($name, $id, $errorKey, $fieldRequired ?? false, $errors, $attributes));

    $checkboxAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'checkbox',
        'data-checkable' => 'true',
        'type' => 'checkbox',
        'id' => $resolvedId,
        'name' => $name ?: null,
        'value' => $value,
        'checked' => $isChecked ? true : null,
        'aria-describedby' => $errorId,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'data-invalid' => $hasErrors ? true : null,
        'aria-required' => $isRequired ? 'true' : null,
        'required' => $isRequired ? true : null,
        'data-controller' => $elementController ?: null,
        'data-checkbox-indeterminate-value' => $indeterminate ? 'true' : null,
        'data-action' => $elementAction ?: null,
        'data-auto-submit-delay-param' => $autoSubmitDelayParam,
        'class' => $class ?: null,
    ], $attributes, $stimulus, except: ['checked', 'required', 'indeterminate', 'auto-submit', 'auto-submit-delay', 'unchecked-value'], protectedPrefixes: $internalPrefixes);
@endphp

@if ($renderUncheckedValue)
    <input type="hidden" name="{{ $name }}" value="{{ $uncheckedValue }}" @if ($hiddenDisabled) disabled @endif />
@endif

<input
    {{ $checkboxAttributes }}
/>
