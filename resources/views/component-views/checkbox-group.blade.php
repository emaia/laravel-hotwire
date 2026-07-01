@aware(['name' => null, 'id' => null, 'errorKey' => null])

@php extract($compute($name, $id, $errorKey, $errors, $attributes)) @endphp

<div data-slot="checkbox-group" @if ($wrapperController) data-controller="{{ $wrapperController }}" @endif
    {{ $attributes->merge(
            filled($wrapperClass)
                ? ['class' => $wrapperClass]
                : []
        )->whereDoesntStartWith(array_merge(['data-controller'], $internalPrefixes))->except(['select-all']) }}
>
    @if ($selectAll)
        @php
            $selectAllId = $baseId ? $baseId.'-all' : null;
        @endphp
        <label data-slot="label" @if (filled($labelClass)) class="{{ $labelClass }}" @endif>
            <input
                data-slot="checkbox-group-input"
                data-checkable="true"
                type="checkbox"
                @if (filled($class)) class="{{ $class }}" @endif
                data-checkbox-select-all-target="checkboxAll"
                @if ($selectAllId) id="{{ $selectAllId }}" @endif
                @if ($errorId) aria-describedby="{{ $errorId }}" @endif
                @if ($hasErrors) aria-invalid="true" data-invalid @endif
            />
            {{ $selectAllLabel ?: 'Select all' }}
        </label>
    @endif

    @foreach ($options as $value => $label)
        @php
            $resolvedId = $baseId ? $baseId.'-'.\Illuminate\Support\Str::slug((string) $value) : null;
        @endphp
        <label data-slot="label" @if (filled($labelClass)) class="{{ $labelClass }}" @endif>
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
                @if ($selectAll) data-checkbox-select-all-target="checkbox" @endif
                @if (in_array($value, $resolvedSelected)) checked @endif
            />
            {{ $label }}
        </label>
    @endforeach
</div>
