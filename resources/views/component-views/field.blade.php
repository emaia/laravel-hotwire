@php
    $name = $name ?? null;
    $errorKey = $errorKey ?? null;
    $required = $required ?? null;
@endphp

<div role="group" data-slot="field" data-orientation="{{ $orientation }}" {{ $attributes->class($class ?: null)->only('class') }}>
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
