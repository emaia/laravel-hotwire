@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null, 'fieldRequired' => false, 'fieldControlContext' => null])

@php
    $explicitName = $name ?? null;
    $id = \Emaia\LaravelHotwire\Support\FieldKey::resolveId($id ?? null, $explicitName, $fieldId, $fieldName);
    $errorKey = \Emaia\LaravelHotwire\Support\FieldKey::resolveErrorKey($errorKey ?? null, $explicitName, $fieldErrorKey, $fieldName);
    $name = $explicitName ?? $fieldName;
    extract($compute($name, $id, $errorKey, $fieldRequired ?? false, $errors, $attributes));
    $errorReference = $fieldControlContext instanceof \Emaia\LaravelHotwire\Support\FieldContext
        ? $fieldControlContext->errorReference($errorId, $name, $resolvedErrorKey)
        : null;
    $describedBy = $describedBy === $errorId ? $errorReference : $describedBy;

    $messageCopy = $messages ?? [];
    $defaultTitle = $messageCopy['button'] ?? $messageCopy[$multiple ? 'idleMultiple' : 'idle'] ?? 'Choose files';
    $dropzoneLabel = (string) ($attributes->get('aria-label') ?: $defaultTitle);
    $dropzoneTitle = $defaultTitle ?: $dropzoneLabel;
    $dropzoneDescription = $messageCopy['hint'] ?? ($multiple ? 'Drop files here or click to choose' : 'Drop a file here or click to choose');
    $removeLabel = $messageCopy['removeFile'] ?? 'Remove file';
    $clearAllLabel = $messageCopy['clearAll'] ?? 'Clear all';
    $retryLabel = $messageCopy['retry'] ?? 'Retry upload';
    $dropzoneActions = implode(' ', [
        "click->{$controller}#openPicker",
        "keydown.enter->{$controller}#openPicker",
        "keydown.space->{$controller}#openPicker",
        "dragenter->{$controller}#dragEnter",
        "dragover->{$controller}#dragOver",
        "dragleave->{$controller}#dragLeave",
        "drop->{$controller}#drop",
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
        "data-{$controller}-target" => 'dropzone',
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
    ]), protectedPrefixes: ['data-slot', 'data-file-upload-dropzone-variant', "data-{$controller}-target"]);

    $fileUploadAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'file-upload',
        'id' => $resolvedId,
        'data-controller' => $controller,
        'data-density' => $density,
        'data-view' => $view,
        "data-{$controller}-url-value" => $url,
        "data-{$controller}-hidden-name-value" => $hiddenName,
        "data-{$controller}-accept-value" => $accept,
        "data-{$controller}-max-size-bytes-value" => $maxSizeBytes,
        "data-{$controller}-max-files-value" => $maxFiles,
        "data-{$controller}-multiple-value" => $multiple ? 'true' : null,
        "data-{$controller}-mode-value" => $mode !== 'managed' ? $mode : null,
        "data-{$controller}-output-mode-value" => $outputMode !== 'full' ? $outputMode : null,
        "data-{$controller}-param-name-value" => $paramName !== 'file' ? $paramName : null,
        "data-{$controller}-response-key-value" => $responseKey !== 'token' ? $responseKey : null,
        "data-{$controller}-preview-url-key-value" => $previewUrlKey !== 'preview_url' ? $previewUrlKey : null,
        "data-{$controller}-delete-url-value" => $deleteUrl,
        "data-{$controller}-parallel-uploads-value" => $parallelUploads !== 3 ? $parallelUploads : null,
        "data-{$controller}-view-value" => $view !== 'list' ? $view : null,
        "data-{$controller}-messages-value" => $messagesJson !== null ? e($messagesJson) : null,
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
        data-{{ $controller }}-target="input"
        data-action="change->{{ $controller }}#select"
    >

    @if ($view === 'image')
        @if ($hasCustomDropzone)
            <div {{ $dropzoneAttributes }}>
                <div data-slot="file-upload-image-base">
                    {{ $dropzoneSlot }}
                </div>
                <img
                    data-slot="file-upload-image-preview"
                    data-{{ $controller }}-target="imagePreview"
                    alt=""
                    hidden
                >
            </div>
        @else
            <div
                data-slot="file-upload-dropzone"
                data-file-upload-dropzone-variant="{{ $resolvedDropzoneVariant }}"
                data-{{ $controller }}-target="dropzone"
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
                    data-{{ $controller }}-target="imagePreview"
                    alt=""
                    hidden
                >
            </div>
        @endif

        <p
            id="{{ $feedbackId }}"
            data-slot="file-upload-feedback"
            data-{{ $controller }}-target="feedback"
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
            data-{{ $controller }}-target="feedback"
            data-file-upload-default-feedback=""
            hidden
        ></p>
    @else
        <div
            data-slot="file-upload-dropzone"
            data-file-upload-dropzone-variant="{{ $resolvedDropzoneVariant }}"
            data-{{ $controller }}-target="dropzone"
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
                        data-{{ $controller }}-target="feedback"
                        data-file-upload-default-feedback="{{ $dropzoneDescription }}"
                    >{{ $dropzoneDescription }}</div>
                </x-hw::empty-state.header>
            </x-hw::empty-state>
        </div>
    @endif

    @if ($isClearable)
        <div data-slot="file-upload-actions">
            <x-hw::button type="button" variant="ghost" size="sm" hidden data-file-upload-clear data-action="{{ $controller }}#clear">
                {{ $clearAllLabel }}
            </x-hw::button>
        </div>
    @endif

    @if ($view !== 'image' && $rendersPreview)
        <div data-slot="attachment-group" role="list" data-{{ $controller }}-target="list"></div>

        <template data-{{ $controller }}-target="template">
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
                    <x-hw::attachment.action hidden data-file-upload-retry data-action="{{ $controller }}#retry" aria-label="{{ $retryLabel }}">
                        <x-hw::icon name="redo-2" />
                    </x-hw::attachment.action>
                    <x-hw::attachment.action data-file-upload-remove data-action="{{ $controller }}#remove" aria-label="{{ $removeLabel }}">
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
        data-{{ $controller }}-target="announcer"
        style="position:absolute;clip:rect(0 0 0 0);clip-path:inset(50%);overflow:hidden;width:1px;height:1px;white-space:nowrap"
    ></div>
</div>
