@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php
    extract($compute($name, $id, $errorKey, $required, $errors, $attributes));

    $fileUploadAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'file-upload',
        'id' => $resolvedId,
        'tabindex' => '0',
        'role' => 'button',
        'aria-label' => $hasAriaLabel ? null : 'Choose files',
        'data-controller' => $mergedController,
        'data-action' => $mergedAction,
        "data-{$identifier}-url-value" => $url,
        "data-{$identifier}-hidden-name-value" => $hiddenName,
        "data-{$identifier}-accept-value" => $accept,
        "data-{$identifier}-max-size-bytes-value" => $maxSizeBytes,
        "data-{$identifier}-max-files-value" => $maxFiles,
        "data-{$identifier}-multiple-value" => $multiple ? 'true' : null,
        "data-{$identifier}-preview-value" => $preview ? null : 'false',
        "data-{$identifier}-emit-hidden-value" => $emitHidden ? null : 'false',
        "data-{$identifier}-param-name-value" => $paramName !== 'file' ? $paramName : null,
        "data-{$identifier}-response-key-value" => $responseKey !== 'token' ? $responseKey : null,
        "data-{$identifier}-delete-url-value" => $deleteUrl,
        "data-{$identifier}-parallel-uploads-value" => $parallelUploads !== 3 ? $parallelUploads : null,
        "data-{$identifier}-turbo-stream-value" => $turboStream ? 'true' : null,
        "data-{$identifier}-options-value" => $optionsJson !== null ? e($optionsJson) : null,
        'aria-describedby' => $errorId,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'data-invalid' => $hasErrors ? true : null,
        'aria-required' => $isRequired ? 'true' : null,
        'class' => trim('dropzone '.$class),
    ], $attributes, $stimulus, except: ['required'], protectedPrefixes: $internalPrefixes);
@endphp

<div
    {{ $fileUploadAttributes }}
>
    @foreach ($initialValues as $val)
        <input type="hidden" name="{{ $hiddenName }}" value="{{ $val }}" data-hw-upload-preserved>
    @endforeach
    @isset($preview_template)
        @if ($preview_template->isNotEmpty())
            <template data-{{ $identifier }}-target="previewTemplate">{!! $preview_template !!}</template>
        @endif
    @endisset
    <div
        data-slot="file-upload-announcer"
        role="status"
        aria-live="polite"
        data-{{ $identifier }}-target="announcer"
        style="position:absolute;clip:rect(0 0 0 0);clip-path:inset(50%);overflow:hidden;width:1px;height:1px;white-space:nowrap"
    ></div>
</div>
