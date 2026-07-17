@aware(['name' => null, 'id' => null, 'errorKey' => null])

@php
    extract($compute($name, $id, $errorKey, $errors, $attributes));

    $checkboxGroupAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'checkbox-group',
        'data-orientation' => $orientation,
        'data-controller' => $wrapperController ?: null,
        'class' => filled($wrapperClass) ? $wrapperClass : null,
    ], $attributes, $stimulus, except: ['select-all', 'auto-submit', 'auto-submit-delay', 'orientation', 'disabled'], protectedPrefixes: $internalPrefixes);
@endphp

<div
    {{ $checkboxGroupAttributes }}
>
    @if ($selectAll)
        @php
            $selectAllId = $baseId ? $baseId.'-all' : null;
        @endphp
        <label data-slot="checkbox-group-item" @if (filled($labelClass)) class="{{ $labelClass }}" @endif>
            <input
                data-slot="checkbox-group-input"
                data-checkable="true"
                type="checkbox"
                @if (filled($class)) class="{{ $class }}" @endif
                data-checkbox-select-all-target="checkboxAll"
                @if ($selectAllId) id="{{ $selectAllId }}" @endif
                @if ($errorId) aria-describedby="{{ $errorId }}" @endif
                @if ($hasErrors) aria-invalid="true" data-invalid @endif
                @if ($disabled) disabled @endif
                @if ($elementAction) data-action="{!! $elementAction !!}" @endif
                @if ($autoSubmitDelayParam !== null) data-auto-submit-delay-param="{{ $autoSubmitDelayParam }}" @endif
            />
            <span data-slot="checkbox-group-item-content">{{ $selectAllLabel ?: 'Select all' }}</span>
        </label>
    @endif

    @foreach ($options as $value => $label)
        @php
            $resolvedId = $baseId ? $baseId.'-'.\Illuminate\Support\Str::slug((string) $value) : null;
        @endphp
        <label data-slot="checkbox-group-item" @if (filled($labelClass)) class="{{ $labelClass }}" @endif>
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
                @if ($disabled) disabled @endif
                @if ($selectAll) data-checkbox-select-all-target="checkbox" @endif
                @if ($elementAction) data-action="{!! $elementAction !!}" @endif
                @if ($autoSubmitDelayParam !== null) data-auto-submit-delay-param="{{ $autoSubmitDelayParam }}" @endif
                @if (in_array($value, $resolvedSelected)) checked @endif
            />
            <span data-slot="checkbox-group-item-content">{{ $label }}</span>
        </label>
    @endforeach

    {{ $slot }}
</div>
