@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null, 'fieldRequired' => false, 'fieldControlContext' => null])

@php
    $explicitName = $name ?? null;
    $id = \Emaia\LaravelHotwire\Support\FieldKey::resolveId($id ?? null, $explicitName, $fieldId, $fieldName);
    $errorKey = \Emaia\LaravelHotwire\Support\FieldKey::resolveErrorKey($errorKey ?? null, $explicitName, $fieldErrorKey, $fieldName);
    $name = $explicitName ?? $fieldName;
    extract($compute($name, $id, $fieldId, $errorKey, $fieldRequired ?? false, $errors ?? new \Illuminate\Support\ViewErrorBag, $attributes));

    $errorReference = null;
    if ($fieldControlContext instanceof \Emaia\LaravelHotwire\Support\FieldContext && $resolvedId) {
        $fieldControlContext->registerControl(
            $resolvedId,
            $name,
            in_array($type, ['radio', 'checkbox'], true) ? $type : 'control',
            $errorId,
            $resolvedErrorKey,
            $isRequired,
        );
        $errorReference = $fieldControlContext->errorReference($errorId, $name, $resolvedErrorKey);
    }

    $inputAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'input',
        'data-checkable' => $isCheckable ? 'true' : 'false',
        'type' => $type,
        'id' => $resolvedId,
        'name' => $name ?: null,
        'value' => $resolvedValue,
        'checked' => $isCheckable && $isChecked ? true : null,
        'aria-describedby' => $errorReference,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'data-invalid' => $hasErrors ? true : null,
        'aria-required' => $isRequired ? 'true' : null,
        'required' => $isRequired ? true : null,
        'data-controller' => $elementController ?: null,
        'data-action' => $elementAction,
        'data-auto-submit-delay-param' => $autoSubmitDelayParam,
        'data-input-mask-mask-value' => $mask !== null ? e($resolvedMask) : null,
        'data-clear-input-target' => $clearable ? 'input' : null,
        'class' => $class ?: null,
    ], $attributes, $stimulus, except: ['required', 'checked', 'auto-submit', 'auto-submit-delay'], protectedPrefixes: $internalPrefixes);

    $hasWrapper = $clearable;
@endphp

@if ($hasWrapper)
<span
    data-slot="input-wrapper"
    data-clearable="{{ $clearable ? 'true' : 'false' }}"
    @if ($wrapperClass !== '') class="{{ $wrapperClass }}" @endif
    @if ($clearable) data-controller="clear-input" @endif
>
@endif

<input
    {{ $inputAttributes }}
/>

@if ($hasWrapper)
    @if ($clearable)
    <button
        type="button"
        class="hidden"
        data-slot="clear-input-button"
        data-clear-input-target="clearButton"
        tabindex="0"
        aria-label="Clear"
    >
        <x-hw::icon name="circle-x" />
    </button>
    @endif
</span>
@endif
