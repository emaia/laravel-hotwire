@php
    $name = $name ?? null;
    $errorKey = $errorKey ?? null;
    $required = $required ?? null;
@endphp

<div role="group" data-slot="field" data-orientation="{{ $orientation }}" {{ $attributes->class($class ?: null)->only('class') }}>
    @if ($label !== null && $label !== '')
        <x-hwc::label :required-label="$requiredLabel">{{ $label }}</x-hwc::label>
    @endif

    {{ $slot }}

    @if ($description !== null && $description !== '')
        <x-hwc::description>{{ $description }}</x-hwc::description>
    @endif

    @if ($error && $name)
        <x-hwc::error :name="$name" :error-key="$errorKey" />
    @endif
</div>
