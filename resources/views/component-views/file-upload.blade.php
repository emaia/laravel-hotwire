@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php extract($compute($name, $id, $errorKey, $required, $errors, $attributes)) @endphp

<div
    id="{{ $resolvedId }}"
    tabindex="0"
    role="button"
    @unless ($hasAriaLabel) aria-label="Choose files" @endunless
    data-controller="{{ $mergedController }}"
    data-action="{{ $mergedAction }}"
    data-{{ $identifier }}-url-value="{{ $url }}"
    @if ($hiddenName !== null) data-{{ $identifier }}-hidden-name-value="{{ $hiddenName }}" @endif
    @if ($accept !== null) data-{{ $identifier }}-accept-value="{{ $accept }}" @endif
    @if ($maxSizeBytes !== null) data-{{ $identifier }}-max-size-bytes-value="{{ $maxSizeBytes }}" @endif
    @if ($maxFiles !== null) data-{{ $identifier }}-max-files-value="{{ $maxFiles }}" @endif
    @if ($multiple) data-{{ $identifier }}-multiple-value="true" @endif
    @unless ($preview) data-{{ $identifier }}-preview-value="false" @endunless
    @unless ($emitHidden) data-{{ $identifier }}-emit-hidden-value="false" @endunless
    @if ($paramName !== 'file') data-{{ $identifier }}-param-name-value="{{ $paramName }}" @endif
    @if ($responseKey !== 'token') data-{{ $identifier }}-response-key-value="{{ $responseKey }}" @endif
    @if ($deleteUrl !== null) data-{{ $identifier }}-delete-url-value="{{ $deleteUrl }}" @endif
    @if ($parallelUploads !== 3) data-{{ $identifier }}-parallel-uploads-value="{{ $parallelUploads }}" @endif
    @if ($turboStream) data-{{ $identifier }}-turbo-stream-value="true" @endif
    @if ($optionsJson !== null) data-{{ $identifier }}-options-value="{{ $optionsJson }}" @endif
    aria-describedby="{{ $errorId }}"
    @if ($hasErrors) aria-invalid="true" data-invalid @endif
    @if ($isRequired) aria-required="true" @endif
    {{ $attributes
        ->merge(['class' => trim('hwc-file-upload dropzone '.$class)])
        ->whereDoesntStartWith(array_merge(['data-controller', 'data-action'], $internalPrefixes))
        ->except(['required']) }}
>
    @foreach ($initialValues as $val)
        <input type="hidden" name="{{ $hiddenName }}" value="{{ $val }}" data-hw-upload-preserved>
    @endforeach
    <div
        role="status"
        aria-live="polite"
        data-{{ $identifier }}-target="announcer"
        style="position:absolute;clip:rect(0 0 0 0);clip-path:inset(50%);overflow:hidden;width:1px;height:1px;white-space:nowrap"
    ></div>
</div>
