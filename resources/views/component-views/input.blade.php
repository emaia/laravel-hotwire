@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php
    extract($compute($name, $id, $errorKey, $required, $errors, $attributes));

    $inputAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'input',
        'data-checkable' => $isCheckable ? 'true' : 'false',
        'type' => $type,
        'id' => $resolvedId,
        'name' => $name ?: null,
        'value' => $resolvedValue,
        'checked' => $isCheckable && $isChecked ? true : null,
        'aria-describedby' => $errorId,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'data-invalid' => $hasErrors ? true : null,
        'aria-required' => $isRequired ? 'true' : null,
        'required' => $isRequired ? true : null,
        'data-controller' => $elementController ?: null,
        'data-action' => $elementAction,
        'data-auto-submit-delay-param' => $autoSubmitDelayParam,
        'data-input-mask-mask-value' => $mask !== null ? e($resolvedMask) : null,
        'data-clear-input-target' => $clearable ? 'input' : null,
        'class' => $class ?: null,
    ], $attributes, $stimulus, except: ['required', 'checked', 'auto-submit', 'auto-submit-delay'], protectedPrefixes: $internalPrefixes);

    $hasIconStart = ! $isCheckable && $iconStart !== null && $iconStart !== '';
    $hasIconEnd = ! $isCheckable && $iconEnd !== null && $iconEnd !== '';
    $hasWrapper = $clearable || $hasIconStart || $hasIconEnd;
@endphp

@if ($hasWrapper)
<span
    data-slot="input-wrapper"
    data-clearable="{{ $clearable ? 'true' : 'false' }}"
    data-icon-start="{{ $hasIconStart ? 'true' : 'false' }}"
    data-icon-end="{{ $hasIconEnd ? 'true' : 'false' }}"
    @if ($wrapperClass !== '') class="{{ $wrapperClass }}" @endif
    @if ($clearable) data-controller="clear-input" @endif
>
    @if ($hasIconStart)
        <x-hw::icon :name="$iconStart" data-slot="input-icon-start" aria-hidden="true" />
    @endif
@endif

<input
    {{ $inputAttributes }}
/>

@if ($hasWrapper)
    @if ($hasIconEnd)
        <x-hw::icon :name="$iconEnd" data-slot="input-icon-end" aria-hidden="true" />
    @endif

    @if ($clearable)
    <button
        type="button"
        class="hidden"
        data-slot="clear-input-button"
        data-clear-input-target="clearButton"
        tabindex="0"
        aria-label="Clear"
    >
        <x-hw::icon name="circle-x" />
    </button>
    @endif
</span>
@endif
