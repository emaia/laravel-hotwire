@aware(['name' => null, 'errorKey' => null, 'id' => null])

@php
    use Emaia\LaravelHotwire\Support\FieldKey;

    $hasName = $name !== null && $name !== '';
    $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : null);
    $resolvedId = $id ?: ($hasName ? FieldKey::toId($name).'-error' : 'hwc-error-'.uniqid());

    $messages = $explicitMessages ?? ($resolvedErrorKey ? $errors->get($resolvedErrorKey) : []);
    $isEmpty = empty($messages);
@endphp

<div
    id="{{ $resolvedId }}"
    role="alert"
    aria-live="polite"
    @class(['hwc-error', $class, 'hidden' => $isEmpty])
    @if ($isEmpty) hidden @endif
>
    @if (count($messages) === 1)
        {{ $messages[0] }}
    @elseif (count($messages) > 1)
        <ul>
            @foreach ($messages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif
</div>
