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
    @elseif ($optional)
        <span class="label-optional">(opcional)</span>
    @endif

    @if ($info)
        <span data-controller="tooltip" data-tooltip-content-value="{{ $info }}"></span>
    @endif
</label>
