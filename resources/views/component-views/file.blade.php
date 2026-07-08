@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php
    extract($compute($name, $id, $errorKey, $required, $errors, $attributes));

    $fileAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'file-input',
        'type' => 'file',
        'id' => $resolvedId,
        'data-controller' => $inputController,
        'name' => $renderName ?: null,
        'multiple' => $multiple ? true : null,
        'data-reset-on-success' => $resetOnSuccess ? 'true' : null,
        'aria-describedby' => $errorId,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'data-invalid' => $hasErrors ? true : null,
        'aria-required' => $isRequired ? 'true' : null,
        'required' => $isRequired ? true : null,
        'class' => $class ?: null,
    ], $attributes, $stimulus, except: ['required'], protectedPrefixes: $internalPrefixes);
@endphp

@if ($needsWrapper)<div @if ($wrapperClass !== '') class="{{ $wrapperClass }}" @endif data-slot="file-wrapper">
    @if ($currentUrl)
        <p>
            {{ $currentLabel ?? 'Current file' }}:
            <a href="{{ $currentUrl }}" target="_blank" rel="noopener">{{ $currentLabel ?? 'Current file' }}</a>
        </p>
    @endif
@endif
    <input
        {{ $fileAttributes }}
    />
@if ($needsWrapper)</div>@endif
