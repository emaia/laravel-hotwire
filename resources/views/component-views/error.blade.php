@aware(['name' => null, 'errorKey' => null, 'id' => null])

@php extract($compute($name, $errorKey, $id, $errors)) @endphp

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
