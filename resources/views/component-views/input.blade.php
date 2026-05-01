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

    $userController = trim($attributes->get('data-controller', ''));

    $elementController = trim(implode(' ', array_filter([
        $userController,
        $autoSelect ? 'auto-select' : null,
        $mask !== null ? 'input-mask' : null,
    ])));

    $internalPrefixes = array_values(array_filter([
        $clearable ? 'data-clear-input-' : null,
        $mask !== null ? 'data-input-mask-' : null,
    ]));

    $hasErrors = $resolvedErrorKey !== '' && $errors->has($resolvedErrorKey);
    $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;
@endphp

@if ($clearable)
<span @class(['hwc-input flex flex-col justify-center items-center relative', $wrapperClass]) data-controller="clear-input">
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
    {{ $attributes->class([$class])->whereDoesntStartWith(array_merge(['data-controller'], $internalPrefixes))->except(['required']) }}
/>

@if ($clearable)
    <button
        type="button"
        data-clear-input-target="clearButton"
        class="clear-input-button absolute inset-y-0- right-3 hidden items-center px-1 pb-0.5 rounded-full"
        tabindex="0"
        aria-label="Clear"
    >&times;</button>
</span>
@endif
