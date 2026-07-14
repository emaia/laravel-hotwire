@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php extract($compute($name, $id, $errorKey, $required, $errors, $attributes)) @endphp

<span data-slot="select-wrapper">
<select
    data-slot="select"
    id="{{ $resolvedId }}"
    @if ($name) name="{{ $name }}" @endif
    aria-describedby="{{ $errorId }}"
    @if ($hasErrors) aria-invalid="true" data-invalid @endif
    @if ($isRequired) aria-required="true" required @endif
    {{ $attributes->merge([
            'class' => $class ?: null,
        ])->except(['required']) }}
>
    @if (! $isMultiple && ($placeholder || $nullable))
        <option value="" @if ($placeholderSelected) selected @endif>{{ $placeholder ?? '' }}</option>
    @endif

    @foreach ($options as $value => $label)
        @php
            $isSelected = $isMultiple
                ? in_array((string) $value, $selectedSet, true)
                : (! $placeholderSelected && (string) $resolvedSelected === (string) $value);
        @endphp
        <option value="{{ $value }}"@if ($isSelected) selected @endif>{{ $label }}</option>
    @endforeach
</select>

<x-hw::icon name="chevron-down" aria-hidden="true" data-slot="select-icon" />
</span>
