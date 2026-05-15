@props([
    'name' => null,
    'errorKey' => null,
    'required' => null,
    'error' => true,
    'class' => '',
])

<div {{ $attributes->class(['field', $class])->only('class') }}>
    {{ $slot }}

    @if ($error && $name)
        <x-hwc::error :name="$name" :error-key="$errorKey" />
    @endif
</div>
