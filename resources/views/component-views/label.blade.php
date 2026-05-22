@aware(['name' => null, 'id' => null, 'required' => false])

@php
    use Emaia\LaravelHotwire\Support\FieldKey;

    $for = $for ?? null;
    $id = $id ?? null;
    $name = $name ?? null;

    $slotHtml = (string) $slot;
    $slotWrapsControl = preg_match('/<(input|select|textarea)\b/i', $slotHtml) === 1;

    if ($for !== null) {
        $resolvedFor = $for;
    } elseif ($slotWrapsControl) {
        $resolvedFor = null;
    } else {
        $resolvedFor = $id ?? ($name ? FieldKey::toId($name) : null);
    }
@endphp

<label
    @if ($resolvedFor) for="{{ $resolvedFor }}" @endif
    {{ $attributes->class(['hwc-label', $class]) }}
>
    {{ trim($slotHtml) !== '' ? $slot : $value }}

    @if ($required)
        <span class="label-required" aria-hidden="true">{{ $requiredLabel }}</span>
    @endif
</label>
