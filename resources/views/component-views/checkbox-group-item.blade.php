@aware([
    'name' => null,
    'id' => null,
    'errorKey' => null,
    'selected' => [],
    'old' => true,
    'selectAll' => false,
    'autoSubmit' => false,
])

@php
    extract($compute($name, $id, $errorKey, $selected, $old, $selectAll, $autoSubmit, $errors, $attributes));
@endphp

<label
    data-slot="checkbox-group-item"
    {{ trim($labelClass) !== '' ? $attributes->merge(['class' => $labelClass]) : $attributes->except('class') }}
>
    <input
        data-slot="checkbox-group-input"
        data-checkable="true"
        type="checkbox"
        @if (filled($class)) class="{{ $class }}" @endif
        @if ($name) name="{{ $name }}" @endif
        value="{{ $value }}"
        @if ($resolvedId) id="{{ $resolvedId }}" @endif
        @if ($errorId) aria-describedby="{{ $errorId }}" @endif
        @if ($hasErrors) aria-invalid="true" data-invalid @endif
        @if ($selectAllTarget) data-checkbox-select-all-target="{{ $selectAllTarget }}" @endif
        @if ($elementAction) data-action="{!! $elementAction !!}" @endif
        @if ($isChecked) checked @endif
    />

    <span data-slot="checkbox-group-item-content">{{ $slot }}</span>
</label>
