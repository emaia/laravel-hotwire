@aware([
    'toggleGroupContext' => false,
    'toggleGroupName' => null,
    'toggleGroupType' => 'multiple',
    'toggleGroupSelected' => [],
    'toggleGroupOld' => true,
    'toggleGroupId' => null,
    'toggleGroupErrorKey' => null,
    'toggleGroupVariant' => 'default',
    'toggleGroupSize' => 'default',
    'toggleGroupDisabled' => false,
    'fieldName' => null,
    'fieldId' => null,
    'fieldErrorKey' => null,
])

@php
    if (! $toggleGroupContext) {
        throw new InvalidArgumentException('Toggle Group item must be rendered inside a Toggle Group root. If a root is present, check for an intermediate component declaring a toggleGroupContext prop, which shadows the root context.');
    }

    extract($compute(
        $toggleGroupItemName ?? $toggleGroupName ?? $fieldName,
        $toggleGroupType,
        $toggleGroupSelected,
        $toggleGroupOld,
        $toggleGroupItemId ?? $toggleGroupId ?? $fieldId,
        $toggleGroupItemErrorKey ?? $toggleGroupErrorKey ?? $fieldErrorKey,
        $toggleGroupVariant,
        $toggleGroupSize,
        $toggleGroupDisabled,
        $errors,
        $attributes,
    ));

    $userClasses = preg_split('/\s+/', trim((string) $attributes->get('class', ''))) ?: [];
    $toggleGroupClass = in_array('group/toggle', $userClasses, true) ? null : 'group/toggle';

    $itemAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => 'button',
        'data-slot' => 'toggle-group-item',
        'data-controller' => 'toggle',
        'data-action' => 'click->toggle#toggle',
        'data-toggle-group-target' => 'item',
        'data-variant' => $variant,
        'data-size' => $size,
        'data-state' => $state,
        'data-disabled' => $isDisabled ? 'true' : null,
        'disabled' => $isDisabled ? true : null,
        'aria-pressed' => $isPressed ? 'true' : 'false',
        'aria-describedby' => $errorId,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'data-invalid' => $hasErrors ? true : null,
        'data-toggle-pressed-value' => $isPressed ? 'true' : 'false',
        'data-toggle-value-value' => $htmlValue,
        'data-toggle-input-id-value' => $inputId,
        'class' => $toggleGroupClass,
    ], $attributes, $toggleGroupItemStimulus, except: ['value', 'pressed', 'disabled', 'name', 'id', 'error-key'], protectedPrefixes: ['data-toggle-', 'data-toggle-group-']);
@endphp

@if ($name)
    <input
        id="{{ $inputId }}"
        data-toggle-input
        type="hidden"
        name="{{ $name }}"
        value="{{ $htmlValue }}"
        @if ($hiddenDisabled) disabled @endif
    />
@endif

<button {{ $itemAttributes }}>{{ $slot }}</button>
