@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null, 'fieldOwnerName' => null, 'fieldOwnerId' => null, 'fieldOwnerErrorKey' => null])

@php
    $ownerId = $fieldOwnerId ?? $fieldId;
    $resolvedContextId = $id ?? ($ownerId ? $ownerId.'-error' : null);
    extract($compute($name ?? $fieldOwnerName ?? $fieldName, $errorKey ?? $fieldOwnerErrorKey ?? $fieldErrorKey, $resolvedContextId, $errors));
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
