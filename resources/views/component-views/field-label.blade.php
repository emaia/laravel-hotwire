@aware(['name' => null, 'id' => null, 'required' => false])

@php extract($compute($name, $id, $slot)) @endphp

<label
    data-slot="field-label"
    @if ($resolvedFor) for="{{ $resolvedFor }}" @endif
    {{ trim($class) !== '' ? $attributes->merge(['class' => $class]) : $attributes->except('class') }}
>
    {{ trim($slotHtml) !== '' ? $slot : $value }}

    @if ($required)
        <span data-slot="field-label-required" aria-hidden="true">{{ $requiredLabel }}</span>
    @endif
</label>
