@props([
    'name' => null,
    'label' => null,
    'description' => null,
    'requiredLabel' => '*',
    'errorKey' => null,
    'required' => null,
    'error' => true,
    'class' => '',
])

<div {{ $attributes->class(['field', $class])->only('class') }}>
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
