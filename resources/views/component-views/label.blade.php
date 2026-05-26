@aware(['name' => null, 'id' => null, 'required' => false])

@php extract($compute($name, $id, $slot)) @endphp

<label
    @if ($resolvedFor) for="{{ $resolvedFor }}" @endif
    {{ $attributes->class(['hwc-label', $class]) }}
>
    {{ trim($slotHtml) !== '' ? $slot : $value }}

    @if ($required)
        <span class="label-required" aria-hidden="true">{{ $requiredLabel }}</span>
    @endif
</label>
