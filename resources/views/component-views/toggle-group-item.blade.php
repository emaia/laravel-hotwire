@aware([
    'name' => null,
    'type' => 'multiple',
    'selected' => [],
    'old' => true,
    'id' => null,
    'errorKey' => null,
    'variant' => 'default',
    'size' => 'default',
    'groupDisabled' => false,
])

@php
    extract($compute($name, $type, $selected, $old, $id, $errorKey, $variant, $size, $groupDisabled, $errors, $attributes));

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
    ], $attributes, $stimulus, except: ['value', 'pressed', 'disabled', 'name', 'id', 'error-key'], protectedPrefixes: ['data-toggle-', 'data-toggle-group-']);
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
