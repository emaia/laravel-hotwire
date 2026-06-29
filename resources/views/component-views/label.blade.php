@aware(['name' => null, 'id' => null, 'required' => false])

@php extract($compute($name, $id, $slot)) @endphp

<label
    @if ($resolvedFor) for="{{ $resolvedFor }}" @endif
    {{ $attributes->class(['text-sm leading-snug font-medium text-foreground peer-disabled:cursor-not-allowed peer-disabled:opacity-50', 'hwc-label', $class]) }}
>
    {{ trim($slotHtml) !== '' ? $slot : $value }}

    @if ($required)
        <span class="hwc-label-required" aria-hidden="true">{{ $requiredLabel }}</span>
    @endif
</label>
