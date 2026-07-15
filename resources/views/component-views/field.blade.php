@php
    $name = $name ?? null;
    $errorKey = $errorKey ?? null;
    $required = $required ?? null;

    $fieldAttributes = $attributes->merge([
        'data-disabled' => $disabled ? 'true' : null,
        'data-invalid' => $invalid ? 'true' : null,
    ])->class($class ?: null);
@endphp

<div role="group" data-slot="field" data-orientation="{{ $orientation }}" {{ $fieldAttributes }}>
    @if ($label !== null && $label !== '')
        <x-hw::field.label :required-label="$requiredLabel">{{ $label }}</x-hw::field.label>
    @endif

    {{ $slot }}

    @if ($description !== null && $description !== '')
        <x-hw::field.description>{{ $description }}</x-hw::field.description>
    @endif

    @if ($error && $name)
        <x-hw::field.error :name="$name" :error-key="$errorKey" />
    @endif
</div>
