@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null, 'fieldOwner' => false, 'fieldOwnerName' => null, 'fieldOwnerId' => null, 'fieldOwnerErrorKey' => null])

@php
    $ownerName = $fieldOwner ? $fieldOwnerName : $fieldName;
    $ownerId = $fieldOwner ? $fieldOwnerId : $fieldId;
    $ownerErrorKey = $fieldOwner ? $fieldOwnerErrorKey : $fieldErrorKey;
    $resolvedName = $name ?? $ownerName;
    $resolvedBaseId = \Emaia\LaravelHotwire\Support\FieldKey::resolveId(null, $name ?? null, $ownerId, $ownerName);
    $resolvedContextId = $id ?? ($resolvedBaseId ? $resolvedBaseId.'-error' : null);
    extract($compute($resolvedName, $errorKey ?? $ownerErrorKey, $resolvedContextId, $errors));
@endphp

<div
    data-slot="field-error"
    data-empty="{{ $isEmpty ? 'true' : 'false' }}"
    id="{{ $resolvedId }}"
    role="alert"
    aria-live="polite"
    @if (filled($class)) class="{{ $class }}" @endif
    @if ($isEmpty) hidden @endif
>
    @if (count($messages) === 1)
        {{ $messages[0] }}
    @elseif (count($messages) > 1)
        <ul>
            @foreach ($messages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif
</div>
