@php
    $fieldAttributes = $attributes->merge([
        'id' => $fieldWrapperId,
        'data-disabled' => $fieldDisabled ? 'true' : null,
        'data-invalid' => $fieldInvalid ? 'true' : null,
    ])->class($fieldClass ?: null);
@endphp

<div role="group" data-slot="field" data-orientation="{{ $fieldOrientation }}" {{ $fieldAttributes }}>
    @if ($fieldLabel !== null && $fieldLabel !== '')
        <x-hw::field.label :required-label="$fieldRequiredLabel">{{ $fieldLabel }}</x-hw::field.label>
    @endif

    {{ $slot }}

    @if ($fieldDescription !== null && $fieldDescription !== '')
        <x-hw::field.description>{{ $fieldDescription }}</x-hw::field.description>
    @endif

    @if ($fieldError && $fieldName)
        <x-hw::field.error :name="$fieldName" :error-key="$fieldErrorKey" />
    @endif
</div>
