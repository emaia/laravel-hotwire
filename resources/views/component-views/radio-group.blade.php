@aware(['name' => null, 'id' => null, 'errorKey' => null])

@php
    extract($compute($name, $id, $errorKey, $errors, $attributes));

    $radioGroupAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'radio-group',
        'data-orientation' => $orientation,
        'class' => filled($wrapperClass) ? $wrapperClass : null,
    ], $attributes, $stimulus, except: ['auto-submit', 'auto-submit-delay', 'orientation', 'disabled'], protectedPrefixes: $internalPrefixes);
@endphp

<div
    {{ $radioGroupAttributes }}
>
    @foreach ($options as $value => $label)
        @php
            $resolvedId = $baseId ? $baseId.'-'.\Illuminate\Support\Str::slug((string) $value) : null;
        @endphp
        <label data-slot="radio-group-item" @if (filled($labelClass)) class="{{ $labelClass }}" @endif>
            <input
                data-slot="radio-group-input"
                data-checkable="true"
                type="radio"
                @if (filled($class)) class="{{ $class }}" @endif
                @if ($name) name="{{ $name }}" @endif
                value="{{ $value }}"
                @if ($resolvedId) id="{{ $resolvedId }}" @endif
                @if ($errorId) aria-describedby="{{ $errorId }}" @endif
                @if ($hasErrors) aria-invalid="true" data-invalid @endif
                @if ($disabled) disabled @endif
                @if ($elementAction) data-action="{!! $elementAction !!}" @endif
                @if ($autoSubmitDelayParam !== null) data-auto-submit-delay-param="{{ $autoSubmitDelayParam }}" @endif
                @if ((string) $resolvedSelected === (string) $value) checked @endif
            />
            <span data-slot="radio-group-item-content">{{ $label }}</span>
        </label>
    @endforeach

    {{ $slot }}
</div>
