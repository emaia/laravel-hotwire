@aware(['fieldName' => null, 'fieldId' => null, 'fieldScope' => null, 'fieldErrorKey' => null, 'fieldRequired' => false, 'fieldControlContext' => null])

@php
    $explicitName = $name ?? null;
    $explicitId = $id ?? null;
    $id = \Emaia\LaravelHotwire\Support\FieldKey::resolveId($explicitId, $explicitName, $fieldId, $fieldName);
    if ($explicitId === null && $explicitName !== null && $explicitName !== '' && $explicitName !== $fieldName) {
        $id = \Emaia\LaravelHotwire\Support\FieldKey::scopedToId($fieldScope, $explicitName);
    }
    $errorKey = \Emaia\LaravelHotwire\Support\FieldKey::resolveErrorKey($errorKey ?? null, $explicitName, $fieldErrorKey, $fieldName);
    $name = $explicitName ?? $fieldName;
    extract($compute($name, $id, $errorKey, $errors ?? new \Illuminate\Support\ViewErrorBag));
    $isRequired = $attributes->has('required') ? $attributes->get('required') !== false : $fieldRequired;
    $errorReference = null;
    if ($fieldControlContext instanceof \Emaia\LaravelHotwire\Support\FieldContext) {
        $fieldControlContext->registerControl($resolvedId, $name, errorId: $errorId, errorKey: $resolvedErrorKey, required: $isRequired);
        $errorReference = $fieldControlContext->errorReference($errorId, $name, $resolvedErrorKey);
    }

    $rootAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => $slotNames['root'],
        'data-controller' => 'input-otp',
        'data-action' => trim('turbo:morph-element->input-otp#refresh '.$completionAction),
        'data-auto-submit-delay-param' => $autoSubmitDelayParam,
        'class' => $wrapperClass ?: null,
    ], $attributes->whereStartsWith('data-')->except(['data-slot', 'data-enhanced']), $stimulus, protectedPrefixes: $internalPrefixes);

    $inputAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => $slotNames['input'],
        'data-input-otp-target' => 'input',
        'data-action' => 'input->input-otp#input change->input-otp#input compositionend->input-otp#input paste->input-otp#paste focus->input-otp#render blur->input-otp#render pointerdown->input-otp#placeCaret pointermove->input-otp#extendSelection pointerup->input-otp#endSelection pointercancel->input-otp#endSelection keydown->input-otp#navigate keyup->input-otp#render select->input-otp#render',
        'type' => 'text',
        'inputmode' => 'numeric',
        'autocomplete' => 'one-time-code',
        'pattern' => '[0-9]{'.$length.'}',
        'minlength' => $length,
        'maxlength' => $length,
        'id' => $resolvedId,
        'name' => $name ?: null,
        'value' => $resolvedValue,
        'required' => $isRequired ? true : null,
        'aria-required' => $isRequired ? 'true' : null,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'aria-describedby' => $errorReference,
        'spellcheck' => 'false',
        'autocapitalize' => 'none',
        'dir' => 'ltr',
        'class' => $class ?: null,
    ], $attributes->whereDoesntStartWith('data-'), except: ['type', 'value', 'inputmode', 'pattern', 'minlength', 'maxlength', 'required']);
@endphp

<span {{ $rootAttributes }}>
    <input {{ $inputAttributes }} />
    <span data-slot="{{ $slotNames['slots'] }}" data-input-otp-target="slots" aria-hidden="true" hidden dir="ltr">
        @for ($index = 0; $index < $length; $index++)
            <span data-slot="{{ $slotNames['slot'] }}" data-input-otp-target="slot"></span>
        @endfor
    </span>
</span>
