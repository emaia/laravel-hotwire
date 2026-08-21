@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null])

@php
    extract($compute($checkboxGroupName ?? $fieldName, $checkboxGroupId ?? $fieldId, $checkboxGroupErrorKey ?? $fieldErrorKey, $errors, $attributes));

    $labelId = $fieldOwnerContext->labelId();
    $hasExplicitLabelledby = $attributes->has('aria-labelledby');

    if ($checkboxGroupFieldContext instanceof \Emaia\LaravelHotwire\Support\FieldContext) {
        $labelId = $checkboxGroupFieldContext->registerSelection($baseId, $name, $labelId, $hasExplicitLabelledby);
    }

    $checkboxGroupAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'checkbox-group',
        'role' => 'group',
        'aria-labelledby' => $labelId,
        'data-orientation' => $checkboxGroupOrientation,
        'data-controller' => $wrapperController ?: null,
        'data-checkbox-select-all-disable-indeterminate-value' => $checkboxGroupSelectAll && $checkboxGroupDisableIndeterminate ? 'true' : null,
        'class' => filled($checkboxGroupWrapperClass) ? $checkboxGroupWrapperClass : null,
    ], $attributes, $checkboxGroupStimulus, except: ['select-all', 'disable-indeterminate', 'auto-submit', 'auto-submit-delay', 'orientation', 'disabled'], protectedPrefixes: $internalPrefixes);
@endphp

<div
    {{ $checkboxGroupAttributes }}
>
    @if ($checkboxGroupSelectAll)
        @php
            $selectAllId = $baseId ? $baseId.'-all' : null;
        @endphp
        <label data-slot="checkbox-group-item" @if (filled($checkboxGroupLabelClass)) class="{{ $checkboxGroupLabelClass }}" @endif>
            <input
                data-slot="checkbox-group-input"
                data-checkable="true"
                type="checkbox"
                @if (filled($checkboxGroupClass)) class="{{ $checkboxGroupClass }}" @endif
                data-checkbox-select-all-target="checkboxAll"
                @if ($selectAllId) id="{{ $selectAllId }}" @endif
                @if ($errorId) aria-describedby="{{ $errorId }}" @endif
                @if ($hasErrors) aria-invalid="true" data-invalid @endif
                @if ($checkboxGroupDisabled) disabled @endif
                @if ($elementAction) data-action="{!! $elementAction !!}" @endif
                @if ($autoSubmitDelayParam !== null) data-auto-submit-delay-param="{{ $autoSubmitDelayParam }}" @endif
            />
            <span data-slot="checkbox-group-item-content">{{ $checkboxGroupSelectAllLabel ?: 'Select all' }}</span>
        </label>
    @endif

    @foreach ($checkboxGroupOptions as $value => $label)
        @php
            $resolvedId = $baseId ? $baseId.'-'.\Illuminate\Support\Str::slug((string) $value) : null;
        @endphp
        <label data-slot="checkbox-group-item" @if (filled($checkboxGroupLabelClass)) class="{{ $checkboxGroupLabelClass }}" @endif>
            <input
                data-slot="checkbox-group-input"
                data-checkable="true"
                type="checkbox"
                @if (filled($checkboxGroupClass)) class="{{ $checkboxGroupClass }}" @endif
                @if ($name) name="{{ $name }}" @endif
                value="{{ $value }}"
                @if ($resolvedId) id="{{ $resolvedId }}" @endif
                @if ($errorId) aria-describedby="{{ $errorId }}" @endif
                @if ($hasErrors) aria-invalid="true" data-invalid @endif
                @if ($checkboxGroupDisabled) disabled @endif
                @if ($checkboxGroupSelectAll) data-checkbox-select-all-target="checkbox" @endif
                @if ($elementAction) data-action="{!! $elementAction !!}" @endif
                @if ($autoSubmitDelayParam !== null) data-auto-submit-delay-param="{{ $autoSubmitDelayParam }}" @endif
                @if (in_array($value, $resolvedSelected)) checked @endif
            />
            <span data-slot="checkbox-group-item-content">{{ $label }}</span>
        </label>
    @endforeach

    {{ $slot }}
</div>
