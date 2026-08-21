@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null, 'fieldRequired' => false])

@php
    $name = $name ?? $fieldName;
    $id = $id ?? $fieldId;
    $errorKey = $errorKey ?? $fieldErrorKey;
    extract($compute($name, $id, $errorKey, $fieldRequired ?? false, $errors, $attributes));

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
        'aria-describedby' => $errorId,
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
