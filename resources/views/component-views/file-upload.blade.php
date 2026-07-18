@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php
    extract($compute($name, $id, $errorKey, $required, $errors, $attributes));

    $messageCopy = $messages ?? [];
    $defaultTitle = $messageCopy['button'] ?? $messageCopy[$multiple ? 'idleMultiple' : 'idle'] ?? 'Choose files';
    $dropzoneLabel = (string) ($attributes->get('aria-label') ?: $defaultTitle);
    $dropzoneTitle = $defaultTitle ?: $dropzoneLabel;
    $dropzoneDescription = $messageCopy['hint'] ?? ($multiple ? 'Drop files here or click to choose' : 'Drop a file here or click to choose');
    $removeLabel = $messageCopy['removeFile'] ?? 'Remove file';
    $dropzoneActions = implode(' ', [
        "click->{$identifier}#openPicker",
        "keydown.enter->{$identifier}#openPicker",
        "keydown.space->{$identifier}#openPicker",
        "dragenter->{$identifier}#dragEnter",
        "dragover->{$identifier}#dragOver",
        "dragleave->{$identifier}#dragLeave",
        "drop->{$identifier}#drop",
    ]);

    $fileUploadAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'file-upload',
        'id' => $resolvedId,
        'data-controller' => $mergedController,
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
        "data-{$identifier}-messages-value" => $messagesJson,
        'aria-describedby' => $describedBy,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'data-invalid' => $hasErrors ? true : null,
        'aria-required' => $isRequired ? 'true' : null,
        'class' => $class ?: null,
    ], $attributes->except(['aria-label', 'options']), $stimulus, except: ['required', 'options'], protectedPrefixes: $internalPrefixes);
@endphp

<div {{ $fileUploadAttributes }}>
    @foreach ($initialValues as $val)
        <input type="hidden" name="{{ $hiddenName }}" value="{{ $val }}" data-hw-upload-preserved>
    @endforeach

    <input
        type="file"
        hidden
        id="{{ $inputId }}"
        name="{{ $paramName }}"
        form="{{ $inputFormId }}"
        @if ($accept) accept="{{ $accept }}" @endif
        @if ($multiple) multiple @endif
        data-{{ $identifier }}-target="input"
        data-action="change->{{ $identifier }}#select"
    >

    <div
        data-slot="file-upload-dropzone"
        data-{{ $identifier }}-target="dropzone"
        data-action="{{ $dropzoneActions }}"
        role="button"
        tabindex="0"
        aria-label="{{ $dropzoneLabel }}"
    >
        <x-hw::empty-state>
            <x-hw::empty-state.header>
                <x-hw::empty-state.media variant="icon">
                    <x-hw::icon name="arrow-up" />
                </x-hw::empty-state.media>
                <x-hw::empty-state.title>{{ $dropzoneTitle }}</x-hw::empty-state.title>
                <x-hw::empty-state.description>{{ $dropzoneDescription }}</x-hw::empty-state.description>
            </x-hw::empty-state.header>
        </x-hw::empty-state>
    </div>

    <div data-slot="attachment-group" role="list" data-{{ $identifier }}-target="list"></div>

    <template data-{{ $identifier }}-target="template">
        <x-hw::attachment state="idle" role="listitem" data-file-upload-attachment>
            <x-hw::attachment.media variant="icon">
                <x-hw::icon name="copy" />
            </x-hw::attachment.media>
            <x-hw::attachment.content>
                <x-hw::attachment.title data-file-upload-name></x-hw::attachment.title>
                <x-hw::attachment.description data-file-upload-description></x-hw::attachment.description>
                <div data-file-upload-progress hidden>
                    <x-hw::progress value="0" data-file-upload-progressbar />
                </div>
            </x-hw::attachment.content>
            <x-hw::attachment.actions>
                <x-hw::attachment.action data-file-upload-remove data-action="{{ $identifier }}#remove" aria-label="{{ $removeLabel }}">
                    <x-hw::icon name="x" />
                </x-hw::attachment.action>
            </x-hw::attachment.actions>
        </x-hw::attachment>
    </template>

    <div
        data-slot="file-upload-announcer"
        role="status"
        aria-live="polite"
        data-{{ $identifier }}-target="announcer"
        style="position:absolute;clip:rect(0 0 0 0);clip-path:inset(50%);overflow:hidden;width:1px;height:1px;white-space:nowrap"
    ></div>
</div>
