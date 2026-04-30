@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php
    use Emaia\LaravelHotwire\Support\FieldKey;
    use Emaia\LaravelHotwire\Support\MaskPresets;

    $hasName = $name !== null && $name !== '';

    $resolvedId = $id ?: ($hasName ? FieldKey::toId($name) : 'hwc-input-'.uniqid());
    $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
    $errorId = $resolvedId.'-error';

    $resolvedValue = ($old && $resolvedErrorKey !== '')
        ? old($resolvedErrorKey, $value)
        : $value;

    $resolvedMask = $mask !== null ? MaskPresets::resolve($mask) : null;

    $needsWrapper = $clearable || $counter !== null;

    $elementController = implode(' ', array_filter([
        $autoSelect ? 'auto-select' : null,
        $mask !== null ? 'input-mask' : null,
    ]));

    $wrapperController = implode(' ', array_filter([
        $clearable ? 'clear-input' : null,
        $counter !== null ? 'char-counter' : null,
    ]));

    $hasErrors = $resolvedErrorKey !== '' && $errors->has($resolvedErrorKey);
    $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;
@endphp

@if ($needsWrapper)
<span @class(['hwc-input', $wrapperClass]) data-controller="{{ $wrapperController }}" @if ($countdown) data-char-counter-countdown-value="true" @endif>
@endif

<input
    type="{{ $type }}"
    id="{{ $resolvedId }}"
    @if ($name) name="{{ $name }}" @endif
    @if ($resolvedValue !== null) value="{{ $resolvedValue }}" @endif
    aria-describedby="{{ $errorId }}"
    @if ($hasErrors) aria-invalid="true" data-invalid @endif
    @if ($isRequired) aria-required="true" required @endif
    @if ($elementController !== '') data-controller="{{ $elementController }}" @endif
    @if ($mask !== null) data-input-mask-mask-value="{{ $resolvedMask }}" @endif
    @if ($clearable) data-clear-input-target="input" @endif
    @if ($counter !== null) data-char-counter-target="input" maxlength="{{ $counter }}" @endif
    {{ $attributes->class([$class])->whereDoesntStartWith(['data-controller', 'data-clear-input-', 'data-char-counter-', 'data-input-mask-'])->except(['required']) }}
/>

@if ($needsWrapper)
    @if ($clearable)
        <button
            type="button"
            data-clear-input-target="clearButton"
            class="clear-input-button"
            tabindex="0"
            aria-label="Clear"
        >&times;</button>
    @endif

    @if ($counter !== null)
        @isset($counterSlot)
            {{ $counterSlot }}
        @else
            <small data-char-counter-target="counter" aria-live="polite"></small>
        @endisset
    @endif
</span>
@endif
