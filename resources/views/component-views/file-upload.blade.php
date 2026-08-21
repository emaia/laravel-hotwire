@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null, 'fieldRequired' => false])

@php
    $id = \Emaia\LaravelHotwire\Support\FieldKey::resolveId($id ?? null, $name ?? null, $fieldId, $fieldName);
    $name = $name ?? $fieldName;
    $errorKey = $errorKey ?? $fieldErrorKey;
    extract($compute($name, $id, $errorKey, $fieldRequired ?? false, $errors, $attributes));

    $messageCopy = $messages ?? [];
    $defaultTitle = $messageCopy['button'] ?? $messageCopy[$multiple ? 'idleMultiple' : 'idle'] ?? 'Choose files';
    $dropzoneLabel = (string) ($attributes->get('aria-label') ?: $defaultTitle);
    $dropzoneTitle = $defaultTitle ?: $dropzoneLabel;
    $dropzoneDescription = $messageCopy['hint'] ?? ($multiple ? 'Drop files here or click to choose' : 'Drop a file here or click to choose');
    $removeLabel = $messageCopy['removeFile'] ?? 'Remove file';
    $clearAllLabel = $messageCopy['clearAll'] ?? 'Clear all';
    $retryLabel = $messageCopy['retry'] ?? 'Retry upload';
    $dropzoneActions = implode(' ', [
        "click->{$identifier}#openPicker",
        "keydown.enter->{$identifier}#openPicker",
        "keydown.space->{$identifier}#openPicker",
        "dragenter->{$identifier}#dragEnter",
        "dragover->{$identifier}#dragOver",
        "dragleave->{$identifier}#dragLeave",
        "drop->{$identifier}#drop",
    ]);
    $hasCustomDropzone = isset($dropzone);
    if ($dropzoneVariant === 'bare' && ! $hasCustomDropzone) {
        throw new \InvalidArgumentException('Bare file-upload dropzones require a named `dropzone` slot.');
    }
    $resolvedDropzoneVariant = $dropzoneVariant === 'auto'
        ? ($hasCustomDropzone ? 'bare' : 'default')
        : $dropzoneVariant;
    $dropzoneSlot = $dropzone ?? new \Illuminate\View\ComponentSlot;
    $feedbackId = $resolvedId.'-feedback';
    $slotDescribedBy = (string) $dropzoneSlot->attributes->get('aria-describedby', '');
    $dropzoneDescribedBy = implode(' ', array_unique(array_filter(
        preg_split('/\s+/', trim(($describedBy ?? '').' '.$slotDescribedBy.' '.$feedbackId)) ?: [],
    )));
    $dropzoneAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'file-upload-dropzone',
        'data-file-upload-dropzone-variant' => $resolvedDropzoneVariant,
        "data-{$identifier}-target" => 'dropzone',
        'data-action' => $dropzoneActions,
        'role' => 'button',
        'tabindex' => '0',
        'aria-label' => $dropzoneLabel,
        'aria-describedby' => $dropzoneDescribedBy ?: null,
        'aria-invalid' => $hasErrors ? 'true' : $dropzoneSlot->attributes->get('aria-invalid'),
        'aria-required' => $isRequired ? 'true' : $dropzoneSlot->attributes->get('aria-required'),
    ], $dropzoneSlot->attributes->except([
        'role',
        'tabindex',
        'aria-describedby',
        'aria-invalid',
        'aria-required',
    ]), protectedPrefixes: ['data-slot', 'data-file-upload-dropzone-variant', "data-{$identifier}-target"]);

    $fileUploadAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'file-upload',
        'id' => $resolvedId,
        'data-controller' => $mergedController,
        'data-density' => $density,
        'data-view' => $view,
        "data-{$identifier}-url-value" => $url,
        "data-{$identifier}-hidden-name-value" => $hiddenName,
        "data-{$identifier}-accept-value" => $accept,
        "data-{$identifier}-max-size-bytes-value" => $maxSizeBytes,
        "data-{$identifier}-max-files-value" => $maxFiles,
        "data-{$identifier}-multiple-value" => $multiple ? 'true' : null,
        "data-{$identifier}-mode-value" => $mode !== 'managed' ? $mode : null,
        "data-{$identifier}-output-mode-value" => $outputMode !== 'full' ? $outputMode : null,
        "data-{$identifier}-param-name-value" => $paramName !== 'file' ? $paramName : null,
        "data-{$identifier}-response-key-value" => $responseKey !== 'token' ? $responseKey : null,
        "data-{$identifier}-preview-url-key-value" => $previewUrlKey !== 'preview_url' ? $previewUrlKey : null,
        "data-{$identifier}-delete-url-value" => $deleteUrl,
        "data-{$identifier}-parallel-uploads-value" => $parallelUploads !== 3 ? $parallelUploads : null,
        "data-{$identifier}-view-value" => $view !== 'list' ? $view : null,
        "data-{$identifier}-messages-value" => $messagesJson !== null ? e($messagesJson) : null,
        'data-invalid' => $hasErrors ? true : null,
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

    @if ($view === 'image')
        @if ($hasCustomDropzone)
            <div {{ $dropzoneAttributes }}>
                <div data-slot="file-upload-image-base">
                    {{ $dropzoneSlot }}
                </div>
                <img
                    data-slot="file-upload-image-preview"
                    data-{{ $identifier }}-target="imagePreview"
                    alt=""
                    hidden
                >
            </div>
        @else
            <div
                data-slot="file-upload-dropzone"
                data-file-upload-dropzone-variant="{{ $resolvedDropzoneVariant }}"
                data-{{ $identifier }}-target="dropzone"
                data-action="{{ $dropzoneActions }}"
                role="button"
                tabindex="0"
                aria-label="{{ $dropzoneLabel }}"
                data-file-upload-default-image
                aria-describedby="{{ $dropzoneDescribedBy }}"
                @if ($hasErrors) aria-invalid="true" @endif
                @if ($isRequired) aria-required="true" @endif
            >
                <div data-slot="file-upload-image-base">
                    <x-hw::icon name="file-up" />
                </div>
                <img
                    data-slot="file-upload-image-preview"
                    data-{{ $identifier }}-target="imagePreview"
                    alt=""
                    hidden
                >
            </div>
        @endif

        <p
            id="{{ $feedbackId }}"
            data-slot="file-upload-feedback"
            data-{{ $identifier }}-target="feedback"
            data-file-upload-default-feedback=""
            hidden
        ></p>
    @elseif ($hasCustomDropzone)
        <div {{ $dropzoneAttributes }}>
            {{ $dropzoneSlot }}
        </div>

        <p
            id="{{ $feedbackId }}"
            data-slot="file-upload-feedback"
            data-{{ $identifier }}-target="feedback"
            data-file-upload-default-feedback=""
            hidden
        ></p>
    @else
        <div
            data-slot="file-upload-dropzone"
            data-file-upload-dropzone-variant="{{ $resolvedDropzoneVariant }}"
            data-{{ $identifier }}-target="dropzone"
            data-action="{{ $dropzoneActions }}"
            role="button"
            tabindex="0"
            aria-label="{{ $dropzoneLabel }}"
            aria-describedby="{{ $dropzoneDescribedBy }}"
            @if ($hasErrors) aria-invalid="true" @endif
            @if ($isRequired) aria-required="true" @endif
        >
            <x-hw::empty-state>
                <x-hw::empty-state.header>
                    <x-hw::empty-state.media variant="icon">
                        <x-hw::icon name="file-up" />
                    </x-hw::empty-state.media>
                    <x-hw::empty-state.title>{{ $dropzoneTitle }}</x-hw::empty-state.title>
                    <div
                        id="{{ $feedbackId }}"
                        data-slot="empty-state-description"
                        data-{{ $identifier }}-target="feedback"
                        data-file-upload-default-feedback="{{ $dropzoneDescription }}"
                    >{{ $dropzoneDescription }}</div>
                </x-hw::empty-state.header>
            </x-hw::empty-state>
        </div>
    @endif

    @if ($isClearable)
        <div data-slot="file-upload-actions">
            <x-hw::button type="button" variant="ghost" size="sm" hidden data-file-upload-clear data-action="{{ $identifier }}#clear">
                {{ $clearAllLabel }}
            </x-hw::button>
        </div>
    @endif

    @if ($view !== 'image' && $rendersPreview)
        <div data-slot="attachment-group" role="list" data-{{ $identifier }}-target="list"></div>

        <template data-{{ $identifier }}-target="template">
            <x-hw::attachment state="idle" :orientation="$attachmentOrientation" role="listitem" data-file-upload-attachment>
                <x-hw::attachment.media variant="icon">
                    <x-hw::icon name="copy" />
                </x-hw::attachment.media>
                <x-hw::attachment.content>
                    <x-hw::attachment.title data-file-upload-name></x-hw::attachment.title>
                    <x-hw::attachment.description data-file-upload-description></x-hw::attachment.description>
                    <div data-file-upload-progress hidden>
                        <x-hw::progress value="0" />
                    </div>
                </x-hw::attachment.content>
                <x-hw::attachment.actions>
                    <x-hw::attachment.action hidden data-file-upload-retry data-action="{{ $identifier }}#retry" aria-label="{{ $retryLabel }}">
                        <x-hw::icon name="redo-2" />
                    </x-hw::attachment.action>
                    <x-hw::attachment.action data-file-upload-remove data-action="{{ $identifier }}#remove" aria-label="{{ $removeLabel }}">
                        <x-hw::icon name="x" />
                    </x-hw::attachment.action>
                </x-hw::attachment.actions>
            </x-hw::attachment>
        </template>
    @endif

    <div
        data-slot="file-upload-announcer"
        role="status"
        aria-live="polite"
        data-{{ $identifier }}-target="announcer"
        style="position:absolute;clip:rect(0 0 0 0);clip-path:inset(50%);overflow:hidden;width:1px;height:1px;white-space:nowrap"
    ></div>
</div>
