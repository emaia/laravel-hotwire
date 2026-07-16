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
@endphp

@if ($clearable)
<span data-slot="input-wrapper" data-clearable="true" @if ($wrapperClass !== '') class="{{ $wrapperClass }}" @endif data-controller="clear-input">
@endif

<input
    {{ $inputAttributes }}
/>

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
</span>
@endif
