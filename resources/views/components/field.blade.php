@props([
    'name' => null,
    'id' => null,
    'errorKey' => null,
    'label' => null,
    'description' => null,
    'required' => false,
    'class' => '',
])

@php
    $resolvedId = $id ?? ($name ? \Emaia\LaravelHotwire\Support\FieldKey::toId($name) : null);
    $resolvedErrorKey = $errorKey ?? ($name ? \Emaia\LaravelHotwire\Support\FieldKey::toErrorKey($name) : null);
@endphp

<div {{ $attributes->class(['hwc-field', $class])->only('class') }}>
    @if ($label)
        <x-hwc::label
            :for="$resolvedId"
            :required="$required"
        >{{ $label }}</x-hwc::label>
    @endif

    @if ($description)
        <p class="hwc-description">{{ $description }}</p>
    @endif

    {{ $slot }}

    <x-hwc::error
        :error-key="$resolvedErrorKey"
        :id="$resolvedId ? $resolvedId.'-error' : null"
    />
</div>
