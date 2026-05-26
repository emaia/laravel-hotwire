@aware(['name' => null, 'id' => null, 'errorKey' => null])

@php extract($compute($name, $id, $errorKey, $errors, $attributes)) @endphp

<div @if ($wrapperController) data-controller="{{ $wrapperController }}" @endif
    {{ $attributes->class([$class])->whereDoesntStartWith(array_merge(['data-controller'], $internalPrefixes))->except(['select-all']) }}
>
    @if ($selectAll)
        @php
            $selectAllId = $baseId ? $baseId.'-all' : null;
        @endphp
        <label class="hwc-label">
            <input
                type="checkbox"
                class="hwc-input"
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
        <label class="hwc-label">
            <input
                type="checkbox"
                class="hwc-input"
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
