@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null, 'fieldRequired' => false, 'fieldControlContext' => null])

@php
    $explicitName = $name ?? null;
    $id = \Emaia\LaravelHotwire\Support\FieldKey::resolveId($id ?? null, $explicitName, $fieldId, $fieldName);
    $errorKey = \Emaia\LaravelHotwire\Support\FieldKey::resolveErrorKey($errorKey ?? null, $explicitName, $fieldErrorKey, $fieldName);
    $name = $explicitName ?? $fieldName;
    extract($compute($name, $id, $errorKey, $fieldRequired ?? false, $errors ?? new \Illuminate\Support\ViewErrorBag, $attributes));

    if ($fieldControlContext instanceof \Emaia\LaravelHotwire\Support\FieldContext && $resolvedId) {
        $fieldControlContext->registerControl($resolvedId, $name, errorId: $errorId, errorKey: $resolvedErrorKey);
    }

    $textareaAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'textarea',
        'id' => $resolvedId,
        'name' => $name ?: null,
        'aria-describedby' => $errorId,
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
@endphp

@if ($needsWrapper)
<span data-slot="textarea-wrapper" @if ($wrapperClass !== '') class="{{ $wrapperClass }}" @endif data-controller="char-counter" @if ($countdown) data-char-counter-countdown-value="true" @endif>
@endif

<textarea
    {{ $textareaAttributes }}
>{{ $resolvedValue }}</textarea>

@if ($needsWrapper)
    @isset($counterSlot)
        {{ $counterSlot }}
    @else
        <small aria-live="polite">
            <span data-char-counter-target="counter">0</span>/{{$counter}}
        </small>
    @endisset
</span>
@endif
