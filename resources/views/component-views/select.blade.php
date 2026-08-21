@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null, 'fieldRequired' => false, 'fieldControlContext' => null])

@php
    $explicitName = $name ?? null;
    $id = \Emaia\LaravelHotwire\Support\FieldKey::resolveId($id ?? null, $explicitName, $fieldId, $fieldName);
    $errorKey = \Emaia\LaravelHotwire\Support\FieldKey::resolveErrorKey($errorKey ?? null, $explicitName, $fieldErrorKey, $fieldName);
    $name = $explicitName ?? $fieldName;
    extract($compute($name, $id, $errorKey, $fieldRequired ?? false, $errors, $attributes));

    if ($fieldControlContext instanceof \Emaia\LaravelHotwire\Support\FieldContext && $resolvedId) {
        $fieldControlContext->registerControl($resolvedId, $name, errorId: $errorId, errorKey: $resolvedErrorKey);
    }

    $selectAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'select',
        'id' => $resolvedId,
        'name' => $name ?: null,
        'aria-describedby' => $errorId,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'data-invalid' => $hasErrors ? true : null,
        'aria-required' => $isRequired ? 'true' : null,
        'required' => $isRequired ? true : null,
        'data-action' => $elementAction,
        'data-auto-submit-delay-param' => $autoSubmitDelayParam,
        'class' => $class ?: null,
    ], $attributes, null, except: ['required', 'auto-submit', 'auto-submit-delay'], protectedPrefixes: $internalPrefixes);
@endphp

<span data-slot="select-wrapper">
<select
    {{ $selectAttributes }}
>
    @if (! $isMultiple && ($placeholder || $nullable))
        <option value="" @if ($placeholderSelected) selected @endif>{{ $placeholder ?? '' }}</option>
    @endif

    @foreach ($options as $value => $label)
        @php
            $isSelected = $isMultiple
                ? in_array((string) $value, $selectedSet, true)
                : (! $placeholderSelected && (string) $resolvedSelected === (string) $value);
        @endphp
        <option value="{{ $value }}"@if ($isSelected) selected @endif>{{ $label }}</option>
    @endforeach
</select>

<x-hw::icon name="chevron-down" aria-hidden="true" data-slot="select-icon" />
</span>
