@php
    $name = $name ?? null;
    $errorKey = $errorKey ?? null;
    $required = $required ?? null;
@endphp

<div role="group" data-slot="field" data-orientation="{{ $orientation }}" {{ $attributes->class($class ?: null)->only('class') }}>
    @if ($label !== null && $label !== '')
        <x-hwc::field.label :required-label="$requiredLabel">{{ $label }}</x-hwc::field.label>
    @endif

    {{ $slot }}

    @if ($description !== null && $description !== '')
        <x-hwc::field.description>{{ $description }}</x-hwc::field.description>
    @endif

    @if ($error && $name)
        <x-hwc::field.error :name="$name" :error-key="$errorKey" />
    @endif
</div>
