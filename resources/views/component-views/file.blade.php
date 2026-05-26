@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php extract($compute($name, $id, $errorKey, $required, $errors, $attributes)) @endphp

<div @class(['hwc-file', $wrapperClass]) data-controller="{{ $wrapperController }}"
    @if ($resetOnSuccess) data-reset-on-success="true" @endif
>
    @if ($currentUrl)
        <p>
            {{ $currentLabel ?? 'Current file' }}:
            <a href="{{ $currentUrl }}" target="_blank" rel="noopener">{{ $currentLabel ?? 'Current file' }}</a>
        </p>
    @endif

    <input
        type="file"
        id="{{ $resolvedId }}"
        @if ($name) name="{{ $name }}" @endif
        aria-describedby="{{ $errorId }}"
        @if ($hasErrors) aria-invalid="true" data-invalid @endif
        @if ($isRequired) aria-required="true" required @endif
        {{ $attributes->class([$class])->whereDoesntStartWith(array_merge(['data-controller'], $internalPrefixes))->except(['required']) }}
    />
</div>
