@aware(['fieldName' => null, 'fieldId' => null, 'fieldRequired' => false])

@php
    $resolvedRequired = $required ?? $fieldRequired ?? false;
    extract($compute($name ?? $fieldName, $fieldId, $slot));
@endphp

<label
    data-slot="field-label"
    @if ($resolvedFor) for="{{ $resolvedFor }}" @endif
    {{ trim($class) !== '' ? $attributes->merge(['class' => $class]) : $attributes->except('class') }}
>
    {{ trim($slotHtml) !== '' ? $slot : $value }}

    @if ($resolvedRequired)
        <span data-slot="field-label-required" aria-hidden="true">{{ $requiredLabel }}</span>
    @endif
</label>
