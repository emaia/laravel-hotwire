@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php
    use Emaia\LaravelHotwire\Support\FieldKey;

    $hasName = $name !== null && $name !== '';

    $resolvedId = $id ?: ($hasName ? FieldKey::toId($name) : 'hwc-select-'.uniqid());
    $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
    $errorId = $resolvedId.'-error';

    $resolvedSelected = ($old && $resolvedErrorKey !== '')
        ? old($resolvedErrorKey, $selected)
        : $selected;

    $placeholderSelected = $resolvedSelected === '' || $resolvedSelected === null;

    $hasErrors = $resolvedErrorKey !== '' && $errors->has($resolvedErrorKey);
    $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;
@endphp

<select
    id="{{ $resolvedId }}"
    @if ($name) name="{{ $name }}" @endif
    aria-describedby="{{ $errorId }}"
    @if ($hasErrors) aria-invalid="true" data-invalid @endif
    @if ($isRequired) aria-required="true" required @endif
    {{ $attributes->class([$class])->except(['required']) }}
>
@if ($placeholder)
<option value="" disabled @if ($placeholderSelected) selected @endif>{{ $placeholder }}</option>
@endif
@foreach ($options as $value => $label)
<option value="{{ $value }}"@if ($resolvedSelected == $value) selected @endif>{{ $label }}</option>
@endforeach
</select>
