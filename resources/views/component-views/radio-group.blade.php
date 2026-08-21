@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null])

@php
    $labelId = \Emaia\LaravelHotwire\Support\FieldLabel::findIn((string) $slot);
    extract($compute($radioGroupName ?? $fieldName, $radioGroupId ?? $fieldId, $radioGroupErrorKey ?? $fieldErrorKey, $errors, $attributes));

    $radioGroupAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'radio-group',
        'role' => 'radiogroup',
        'aria-labelledby' => $labelId,
        'data-orientation' => $radioGroupOrientation,
        'class' => filled($radioGroupWrapperClass) ? $radioGroupWrapperClass : null,
    ], $attributes, $radioGroupStimulus, except: ['auto-submit', 'auto-submit-delay', 'orientation', 'disabled'], protectedPrefixes: $internalPrefixes);
@endphp

<div
    {{ $radioGroupAttributes }}
>
    @foreach ($radioGroupOptions as $value => $label)
        @php
            $resolvedId = $baseId ? $baseId.'-'.\Illuminate\Support\Str::slug((string) $value) : null;
        @endphp
        <label data-slot="radio-group-item" @if (filled($radioGroupLabelClass)) class="{{ $radioGroupLabelClass }}" @endif>
            <input
                data-slot="radio-group-input"
                data-checkable="true"
                type="radio"
                @if (filled($radioGroupClass)) class="{{ $radioGroupClass }}" @endif
                @if ($name) name="{{ $name }}" @endif
                value="{{ $value }}"
                @if ($resolvedId) id="{{ $resolvedId }}" @endif
                @if ($errorId) aria-describedby="{{ $errorId }}" @endif
                @if ($hasErrors) aria-invalid="true" data-invalid @endif
                @if ($radioGroupDisabled) disabled @endif
                @if ($elementAction) data-action="{!! $elementAction !!}" @endif
                @if ($autoSubmitDelayParam !== null) data-auto-submit-delay-param="{{ $autoSubmitDelayParam }}" @endif
                @if ((string) $resolvedSelected === (string) $value) checked @endif
            />
            <span data-slot="radio-group-item-content">{{ $label }}</span>
        </label>
    @endforeach

    {{ $slot }}
</div>
