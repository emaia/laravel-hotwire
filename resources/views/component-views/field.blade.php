@php
    $resolvedContext = $fieldContext->resolve();

    $fieldAttributes = $attributes->merge([
        'id' => $fieldWrapperId,
        'role' => $resolvedContext['role'],
        'aria-labelledby' => $resolvedContext['ariaLabelledby'],
        'data-disabled' => $fieldDisabled ? 'true' : null,
        'data-invalid' => $fieldInvalid ? 'true' : null,
    ])->class($fieldClass ?: null);
@endphp

<div data-slot="field" data-orientation="{{ $fieldOrientation }}" {{ $fieldAttributes }}>
    @if ($resolvedContext['renderLabel'])
        @if ($resolvedContext['labelFor'] === '' && ! $resolvedContext['labelSet'])
            <span data-slot="field-label">{{ $fieldLabel }}@if ($fieldRequired)<span data-slot="field-label-required" aria-hidden="true">{{ $fieldRequiredLabel }}</span>@endif</span>
        @else
            <x-hw::field.label
                :id="$resolvedContext['labelId']"
                :for="$resolvedContext['labelFor']"
                :set="$resolvedContext['labelSet']"
                :required-label="$fieldRequiredLabel"
            >{{ $fieldLabel }}</x-hw::field.label>
        @endif
    @endif

    {{ $slot }}

    @if ($fieldDescription !== null && $fieldDescription !== '')
        <x-hw::field.description>{{ $fieldDescription }}</x-hw::field.description>
    @endif

    @if ($fieldError && $fieldName)
        <x-hw::field.error :name="$fieldName" :error-key="$fieldErrorKey" />
    @endif
</div>
