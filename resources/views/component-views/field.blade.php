@php
    $slotHtml = (string) $slot;

    // A slot holding a selection group or several radios has no single labelable control,
    // so the auto label drops `for` and this container names itself against the label id.
    $slotHoldsSet = preg_match('/data-slot="(radio-group|checkbox-group|toggle-group)"/', $slotHtml) === 1
        || preg_match('/<input[^>]*type="radio"/i', $slotHtml) === 1;

    $labelId = $slotHoldsSet
        ? \Emaia\LaravelHotwire\Support\FieldLabel::idFor($fieldId, $fieldName)
        : null;

    $fieldAttributes = $attributes->merge([
        'id' => $fieldWrapperId,
        'aria-labelledby' => $slotHoldsSet && $fieldLabel !== null && $fieldLabel !== '' ? $labelId : null,
        'data-disabled' => $fieldDisabled ? 'true' : null,
        'data-invalid' => $fieldInvalid ? 'true' : null,
    ])->class($fieldClass ?: null);
@endphp

<div role="group" data-slot="field" data-orientation="{{ $fieldOrientation }}" {{ $fieldAttributes }}>
    @if ($fieldLabel !== null && $fieldLabel !== '')
        <x-hw::field.label :required-label="$fieldRequiredLabel" :set="$slotHoldsSet">{{ $fieldLabel }}</x-hw::field.label>
    @endif

    {{ $slot }}

    @if ($fieldDescription !== null && $fieldDescription !== '')
        <x-hw::field.description>{{ $fieldDescription }}</x-hw::field.description>
    @endif

    @if ($fieldError && $fieldName)
        <x-hw::field.error :name="$fieldName" :error-key="$fieldErrorKey" />
    @endif
</div>
