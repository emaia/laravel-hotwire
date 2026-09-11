@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null, 'fieldRequired' => false, 'fieldControlContext' => null])

@php
    $explicitName = $name ?? null;
    $id = \Emaia\LaravelHotwire\Support\FieldKey::resolveId($id ?? null, $explicitName, $fieldId, $fieldName);
    $errorKey = \Emaia\LaravelHotwire\Support\FieldKey::resolveErrorKey($errorKey ?? null, $explicitName, $fieldErrorKey, $fieldName);
    $name = $explicitName ?? $fieldName;
    $counter = $guardCounter($counter, $counterSlot ?? null);
    extract($compute($name, $id, $errorKey, $fieldRequired ?? false, $errors ?? new \Illuminate\Support\ViewErrorBag, $attributes));

    $errorReference = null;
    if ($fieldControlContext instanceof \Emaia\LaravelHotwire\Support\FieldContext && $resolvedId) {
        $fieldControlContext->registerControl($resolvedId, $name, errorId: $errorId, errorKey: $resolvedErrorKey, required: $isRequired);
        $errorReference = $fieldControlContext->errorReference($errorId, $name, $resolvedErrorKey);
    }

    $textareaAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => $slotName,
        'id' => $resolvedId,
        'name' => $name ?: null,
        'aria-describedby' => $errorReference,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'data-invalid' => $hasErrors ? true : null,
        'aria-required' => $isRequired ? 'true' : null,
        'required' => $isRequired ? true : null,
        'data-controller' => $elementController ?: null,
        'data-action' => $elementAction,
        'data-auto-submit-delay-param' => $autoSubmitDelayParam,
        'data-char-counter-target' => $counter !== null ? 'input' : null,
        'maxlength' => $counter,
        'class' => $class ?: null,
    ], $attributes, $stimulus, except: ['required', 'auto-submit', 'auto-submit-delay'], protectedPrefixes: $internalPrefixes);

    $counterAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => $counterSlotName,
        'aria-live' => 'polite',
    ], isset($counterSlot) ? $counterSlot->attributes : null, protectedPrefixes: ['data-slot', 'aria-live']);
@endphp

@if ($needsWrapper)
<span data-slot="{{ $wrapperSlotName }}" @if ($wrapperClass !== '') class="{{ $wrapperClass }}" @endif data-controller="char-counter" @if ($countdown) data-char-counter-countdown-value="true" @endif>
@endif

<textarea
    {{ $textareaAttributes }}
>{{ $resolvedValue }}</textarea>

@if ($needsWrapper)
    <small {{ $counterAttributes }}>
        @isset($counterSlot)
            {{ $counterSlot }}
        @else
            <span data-char-counter-target="counter">0</span>/{{ $counter }}
        @endisset
    </small>
</span>
@endif
