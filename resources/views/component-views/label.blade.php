@aware(['name' => null, 'id' => null, 'required' => false])

@php
    use Emaia\LaravelHotwire\Support\FieldKey;

    $resolvedFor = $for
        ?? $id
        ?? ($name ? FieldKey::toId($name) : null);
@endphp

<label
    @if ($resolvedFor) for="{{ $resolvedFor }}" @endif
    {{ $attributes->class(['label', $class]) }}
>
    {{ trim($slot) !== '' ? $slot : $value }}

    @if ($required)
        <span class="label-required" aria-hidden="true">{{ $requiredLabel }}</span>
    @endif
</label>
