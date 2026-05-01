@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php
    use Emaia\LaravelHotwire\Support\FieldKey;

    $hasName = $name !== null && $name !== '';

    $resolvedId = $id ?: ($hasName ? FieldKey::toId($name) : 'hwc-textarea-'.uniqid());
    $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
    $errorId = $resolvedId.'-error';

    $resolvedValue = ($old && $resolvedErrorKey !== '')
        ? old($resolvedErrorKey, $value)
        : $value;

    $hasErrors = $resolvedErrorKey !== '' && $errors->has($resolvedErrorKey);
    $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;

    $needsWrapper = $counter !== null;

    $userController = trim($attributes->get('data-controller', ''));

    $elementController = trim(implode(' ', array_filter([
        $userController,
        $autoResize ? 'auto-resize' : null,
    ])));

    $internalPrefixes = array_values(array_filter([
        $counter !== null ? 'data-char-counter-' : null,
    ]));
@endphp

@if ($needsWrapper)
<span @class(['hwc-textarea', $wrapperClass]) data-controller="char-counter" @if ($countdown) data-char-counter-countdown-value="true" @endif>
@endif

<textarea
    id="{{ $resolvedId }}"
    @if ($name) name="{{ $name }}" @endif
    aria-describedby="{{ $errorId }}"
    @if ($hasErrors) aria-invalid="true" data-invalid @endif
    @if ($isRequired) aria-required="true" required @endif
    @if ($elementController !== '') data-controller="{{ $elementController }}" @endif
    @if ($counter !== null) data-char-counter-target="input" maxlength="{{ $counter }}" @endif
    {{ $attributes->class([$class])->whereDoesntStartWith(array_merge(['data-controller'], $internalPrefixes))->except(['required']) }}
>{{ $resolvedValue }}</textarea>

@if ($needsWrapper)
    @isset($counterSlot)
        {{ $counterSlot }}
    @else
        <small aria-live="polite">
            <span data-char-counter-target="counter">0</span>/{{$counter}}
        </small>
    @endisset
</span>
@endif
