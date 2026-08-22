@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null, 'fieldRequired' => false, 'fieldControlContext' => null])

@php
    $explicitName = $name ?? null;
    $id = \Emaia\LaravelHotwire\Support\FieldKey::resolveId($id ?? null, $explicitName, $fieldId, $fieldName);
    $errorKey = \Emaia\LaravelHotwire\Support\FieldKey::resolveErrorKey($errorKey ?? null, $explicitName, $fieldErrorKey, $fieldName);
    $name = $explicitName ?? $fieldName;
    extract($compute($name, $id, $errorKey, $fieldRequired ?? false, $errors, $attributes));

    $errorReference = null;
    if ($fieldControlContext instanceof \Emaia\LaravelHotwire\Support\FieldContext && $resolvedId) {
        $fieldControlContext->registerControl($resolvedId, $renderName, errorId: $errorId, errorKey: $resolvedErrorKey, required: $isRequired);
        $errorReference = $fieldControlContext->errorReference($errorId, $renderName, $resolvedErrorKey);
    }

    $fileAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'file-input',
        'type' => 'file',
        'id' => $resolvedId,
        'data-controller' => $inputController,
        'name' => $renderName ?: null,
        'multiple' => $multiple ? true : null,
        'data-reset-on-success' => $resetOnSuccess ? 'true' : null,
        'aria-describedby' => $errorReference,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'data-invalid' => $hasErrors ? true : null,
        'aria-required' => $isRequired ? 'true' : null,
        'required' => $isRequired ? true : null,
        'class' => $class ?: null,
    ], $attributes, $stimulus, except: ['required'], protectedPrefixes: $internalPrefixes);
@endphp

@if ($needsWrapper)<div @if ($wrapperClass !== '') class="{{ $wrapperClass }}" @endif data-slot="file-wrapper">
    @if ($currentUrl)
        <p>
            {{ $currentLabel ?? 'Current file' }}:
            <a href="{{ $currentUrl }}" target="_blank" rel="noopener">{{ $currentLabel ?? 'Current file' }}</a>
        </p>
    @endif
@endif
    <input
        {{ $fileAttributes }}
    />
@if ($needsWrapper)</div>@endif
