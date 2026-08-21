@aware(['fieldName' => null, 'fieldId' => null, 'fieldRequired' => false, 'fieldOwner' => false, 'fieldOwnerName' => null, 'fieldOwnerId' => null])

@php
    $resolvedRequired = $required ?? $fieldRequired ?? false;
    $ownerName = $fieldOwner ? $fieldOwnerName : $fieldName;
    $ownerId = $fieldOwner ? $fieldOwnerId : $fieldId;
    extract($compute($name ?? $ownerName, $ownerId, $slot));
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
