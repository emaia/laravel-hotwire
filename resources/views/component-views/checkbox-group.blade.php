@aware(['name' => null, 'id' => null, 'errorKey' => null])

@php extract($compute($name, $id, $errorKey, $errors, $attributes)) @endphp

<div @if ($wrapperController) data-controller="{{ $wrapperController }}" @endif
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
        <x-hwc::label class="{{ $labelClass }}">
            <input
                type="checkbox"
                @class([
                    'hwc-input',
                    'size-4 shrink-0 accent-primary outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40',
                    $class => filled($class),
                ])
                data-checkbox-select-all-target="checkboxAll"
                @if ($selectAllId) id="{{ $selectAllId }}" @endif
                @if ($errorId) aria-describedby="{{ $errorId }}" @endif
                @if ($hasErrors) aria-invalid="true" data-invalid @endif
            />
            {{ $selectAllLabel ?: 'Select all' }}
        </x-hwc::label>
    @endif

    @foreach ($options as $value => $label)
        @php
            $resolvedId = $baseId ? $baseId.'-'.\Illuminate\Support\Str::slug((string) $value) : null;
        @endphp
        <x-hwc::label class="{{ $labelClass }}">
            <input
                type="checkbox"
                @class([
                    'hwc-input',
                    'size-4 shrink-0 accent-primary outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40',
                    $class => filled($class),
                ])
                @if ($name) name="{{ $name }}" @endif
                value="{{ $value }}"
                @if ($resolvedId) id="{{ $resolvedId }}" @endif
                @if ($errorId) aria-describedby="{{ $errorId }}" @endif
                @if ($hasErrors) aria-invalid="true" data-invalid @endif
                @if ($selectAll) data-checkbox-select-all-target="checkbox" @endif
                @if (in_array($value, $resolvedSelected)) checked @endif
            />
            {{ $label }}
        </x-hwc::label>
    @endforeach
</div>
