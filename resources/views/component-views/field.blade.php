@php
    $name = $name ?? null;
    $errorKey = $errorKey ?? null;
    $required = $required ?? null;
@endphp

<div role="group" data-slot="field" data-orientation="{{ $orientation }}" {{ $attributes->class($class ?: null)->only('class') }}>
    @if ($label !== null && $label !== '')
        <hw:field.label :required-label="$requiredLabel">{{ $label }}</hw:field.label>
    @endif

    {{ $slot }}

    @if ($description !== null && $description !== '')
        <hw:field.description>{{ $description }}</hw:field.description>
    @endif

    @if ($error && $name)
        <hw:field.error :name="$name" :error-key="$errorKey" />
    @endif
</div>
