@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php extract($compute($name, $id, $errorKey, $required, $errors, $attributes)) @endphp

@if ($needsWrapper)<div @class(['hwc-file', $wrapperClass])>
    @if ($currentUrl)
        <p>
            {{ $currentLabel ?? 'Current file' }}:
            <a href="{{ $currentUrl }}" target="_blank" rel="noopener">{{ $currentLabel ?? 'Current file' }}</a>
        </p>
    @endif
@endif
    <input
        type="file"
        id="{{ $resolvedId }}"
        data-controller="{{ $inputController }}"
        @if ($renderName) name="{{ $renderName }}" @endif
        @if ($multiple) multiple @endif
        @if ($resetOnSuccess) data-reset-on-success="true" @endif
        aria-describedby="{{ $errorId }}"
        @if ($hasErrors) aria-invalid="true" data-invalid @endif
        @if ($isRequired) aria-required="true" required @endif
        {{ $attributes->class([$class])->whereDoesntStartWith(array_merge(['data-controller'], $internalPrefixes))->except(['required']) }}
    />
@if ($needsWrapper)</div>@endif
