@props([
    'name' => null,
    'errorKey' => null,
    'required' => null,
    'class' => '',
])

<div {{ $attributes->class(['field', $class])->only('class') }}>
    {{ $slot }}
</div>
