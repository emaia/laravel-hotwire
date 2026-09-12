@aware(['fieldName' => null, 'fieldId' => null, 'fieldScope' => null, 'fieldRequired' => false, 'fieldOwner' => false, 'fieldOwnerName' => null, 'fieldOwnerId' => null, 'fieldOwnerSet' => false, 'fieldOwnerContext' => null])

@php
    $resolvedRequired = $required ?? $fieldRequired ?? false;
    $ownerName = $fieldOwner ? $fieldOwnerName : $fieldName;
    $ownerId = $fieldOwner ? $fieldOwnerId : $fieldId;
    $explicitName = $name ?? null;
    $resolvedName = $explicitName ?? $ownerName;
    $resolvedTargetId = \Emaia\LaravelHotwire\Support\FieldKey::resolveId(null, $explicitName, $ownerId, $ownerName);
    if ($explicitName !== null && $explicitName !== '' && $explicitName !== $ownerName) {
        $resolvedTargetId = \Emaia\LaravelHotwire\Support\FieldKey::scopedIdFor($fieldScope, $explicitName);
    }
    extract($compute($resolvedName, $resolvedTargetId, $slot, $set ?? $fieldOwnerSet, $fieldOwnerContext));
@endphp

<label
    data-slot="{{ $slotName }}"
    @if ($resolvedId) id="{{ $resolvedId }}" @endif
    @if ($resolvedFor) for="{{ $resolvedFor }}" @endif
    {{ trim($class) !== '' ? $attributes->merge(['class' => $class]) : $attributes->except('class') }}
>
    {{ trim($slotHtml) !== '' ? $slot : $value }}

    @if ($resolvedRequired)
        <span data-slot="{{ $requiredSlotName }}" aria-hidden="true">{{ $requiredLabel }}</span>
    @endif
</label>
