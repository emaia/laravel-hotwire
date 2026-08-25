<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;

class FileUpload extends Component
{
    use StripsNullProps;

    private const MESSAGE_KEYS = [
        'idle',
        'idleMultiple',
        'hint',
        'button',
        'uploading',
        'uploaded',
        'uploadFailed',
        'serverRejected',
        'clearAll',
        'cleared',
        'removed',
        'removeFile',
        'deleteFailed',
        'retry',
        'fileTooBig',
        'invalidFileType',
        'maxFilesExceeded',
    ];

    private const DENSITIES = ['default', 'compact'];

    private const DROPZONE_VARIANTS = ['auto', 'default', 'bare'];

    private const MODES = ['managed', 'turbo-stream'];

    private const NON_EMPTY_MESSAGE_KEYS = ['idle', 'idleMultiple', 'button'];

    private const OUTPUT_MODES = ['full', 'preview', 'hidden', 'none'];

    private const VIEWS = ['list', 'grid', 'image'];

    public string $outputMode;

    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public ?string $errorKey = null,
        public ?string $url = null,
        public ?string $accept = null,
        public ?int $maxSizeBytes = null,
        public ?int $maxFiles = null,
        public bool $multiple = false,
        public string $mode = 'managed',
        ?string $outputMode = null,
        public string $paramName = 'file',
        public string $responseKey = 'token',
        public ?string $deleteUrl = null,
        public int $parallelUploads = 3,
        public ?bool $clearable = null,
        public string $density = 'default',
        public string $view = 'list',
        public mixed $value = null,
        public ?array $messages = null,
        public string $class = '',
        public string $controller = 'file-upload',
        public ?Htmlable $stimulus = null,
        public string $previewUrlKey = 'preview_url',
        public string $dropzoneVariant = 'auto',
    ) {
        if ($url === null || $url === '') {
            throw new InvalidArgumentException('hw:file-upload requires a `url` prop.');
        }

        if (! preg_match('/^[a-z0-9][a-z0-9_-]*(?:--[a-z0-9][a-z0-9_-]*)*$/', $controller)) {
            throw new InvalidArgumentException('Invalid file-upload controller identifier.');
        }

        if (! in_array($density, self::DENSITIES, true)) {
            throw new InvalidArgumentException('Unsupported file-upload density. Supported values: default, compact.');
        }

        if (! in_array($view, self::VIEWS, true)) {
            throw new InvalidArgumentException('Unsupported file-upload view. Supported values: list, grid, image.');
        }

        if (! in_array($dropzoneVariant, self::DROPZONE_VARIANTS, true)) {
            throw new InvalidArgumentException('Unsupported file-upload dropzone variant. Supported values: auto, default, bare.');
        }

        if (! in_array($mode, self::MODES, true)) {
            throw new InvalidArgumentException('Unsupported file-upload mode. Supported values: managed, turbo-stream.');
        }

        if ($outputMode !== null && ! in_array($outputMode, self::OUTPUT_MODES, true)) {
            throw new InvalidArgumentException('Unsupported file-upload output mode. Supported values: full, preview, hidden, none.');
        }

        $this->outputMode = $outputMode ?? ($mode === 'turbo-stream' ? 'none' : 'full');

        if ($mode === 'turbo-stream' && $this->outputMode !== 'none') {
            throw new InvalidArgumentException('Turbo Stream file uploads require output-mode="none".');
        }

        if ($view === 'image' && $multiple) {
            throw new InvalidArgumentException('Image file-upload view only supports single-file uploads.');
        }

        if ($view === 'image' && $clearable === true) {
            throw new InvalidArgumentException('Image file-upload view does not support clearable.');
        }

        $globalMessages = config('hotwire.file_upload.messages', []);
        if (! is_array($globalMessages)) {
            throw new InvalidArgumentException('File-upload messages configuration must be an array.');
        }

        $this->validateMessages($globalMessages, 'hotwire.file_upload.messages');
        $this->validateMessages($messages ?? []);
        $this->messages = array_replace($globalMessages, $messages ?? []);
        if ($this->messages === []) {
            $this->messages = null;
        }

        $this->accept = $this->normalizeAccept($accept ?? ($view === 'image' ? 'image/*' : null));
    }

    public function render()
    {
        return view('hotwire::component-views.file-upload');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['density'] = $this->density;
        $data['view'] = $this->view;
        $data['internalPrefixes'] = [
            "data-{$this->controller}-url-",
            "data-{$this->controller}-hidden-name-",
            "data-{$this->controller}-accept-",
            "data-{$this->controller}-max-size-bytes-",
            "data-{$this->controller}-max-files-",
            "data-{$this->controller}-multiple-",
            "data-{$this->controller}-mode-",
            "data-{$this->controller}-output-mode-",
            "data-{$this->controller}-param-name-",
            "data-{$this->controller}-response-key-",
            "data-{$this->controller}-preview-url-key-",
            "data-{$this->controller}-delete-url-",
            "data-{$this->controller}-parallel-uploads-",
            "data-{$this->controller}-view-",
            "data-{$this->controller}-messages-",
        ];
        $data['compute'] = $this->computeResolved(...);

        return $this->stripNullProps($data, ['name', 'id', 'errorKey']);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeResolved(
        ?string $name,
        ?string $id,
        ?string $errorKey,
        bool $required,
        ViewErrorBag $errorsBag,
        ComponentAttributeBag $attributes,
    ): array {
        $hasName = $name !== null && $name !== '';

        $resolvedId = $id ?: ($hasName ? FieldKey::toId($name) : 'hw-file-upload-'.uniqid());
        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $errorId = $resolvedId.'-error';
        $describedBy = null;

        $hiddenName = null;
        if ($hasName) {
            $hiddenName = $this->multiple && ! str_ends_with($name, '[]')
                ? $name.'[]'
                : $name;
        }

        $hasErrors = $resolvedErrorKey !== ''
            && ($errorsBag->has($resolvedErrorKey) || $errorsBag->has($resolvedErrorKey.'.*'));
        if ($hasErrors) {
            $describedBy = $errorId;
        }

        $isRequired = $attributes->has('required')
            ? $attributes->get('required') !== false
            : $required;

        $initialValues = $hasName ? $this->resolveInitialValues($name) : [];

        return [
            'resolvedId' => $resolvedId,
            'inputId' => $resolvedId.'-input',
            'inputFormId' => $resolvedId.'-input-owner',
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $errorId,
            'describedBy' => $describedBy,
            'hiddenName' => $hiddenName,
            'hasErrors' => $hasErrors,
            'isRequired' => $isRequired,
            'isClearable' => $this->clearable ?? ($this->multiple && ($this->rendersPreview() || $this->emitsHidden())),
            'attachmentOrientation' => $this->view === 'grid' ? 'vertical' : 'horizontal',
            'initialValues' => $initialValues,
            'messagesJson' => $this->resolveMessagesJson(),
            'rendersPreview' => $this->rendersPreview(),
        ];
    }

    private function rendersPreview(): bool
    {
        return in_array($this->outputMode, ['full', 'preview'], true);
    }

    private function emitsHidden(): bool
    {
        return in_array($this->outputMode, ['full', 'hidden'], true);
    }

    private function resolveMessagesJson(): ?string
    {
        if (($this->messages ?? []) === []) {
            return null;
        }

        return json_encode($this->messages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @param array<string, mixed> $messages */
    private function validateMessages(array $messages, ?string $configPath = null): void
    {
        foreach ($messages as $key => $value) {
            $source = $configPath === null ? '' : " in config [{$configPath}]";
            if (! in_array($key, self::MESSAGE_KEYS, true)) {
                $supported = implode(', ', self::MESSAGE_KEYS);
                throw new InvalidArgumentException(
                    "Unknown file-upload message key [{$key}]{$source}. Supported keys: {$supported}. ".
                    'Use one of the native message keys.'
                );
            }

            if (! is_string($value)) {
                throw new InvalidArgumentException("File-upload message [{$key}]{$source} must be a string.");
            }

            if (in_array($key, self::NON_EMPTY_MESSAGE_KEYS, true) && trim($value) === '') {
                throw new InvalidArgumentException("File-upload message [{$key}]{$source} must not be empty.");
            }
        }
    }

    private function normalizeAccept(?string $accept): ?string
    {
        if ($accept === null) {
            return null;
        }

        $rules = array_values(array_filter(
            array_map(fn (string $rule): string => strtolower(trim($rule)), explode(',', $accept)),
            fn (string $rule): bool => $rule !== '',
        ));

        return $rules === [] ? null : implode(',', $rules);
    }

    /**
     * Resolve preserved hidden values, honouring `old()` over the `value` prop and normalising
     * scalar/array shapes. Empty entries are dropped so the view never emits `value=""` hiddens.
     *
     * @return string[]
     */
    private function resolveInitialValues(string $name): array
    {
        $resolved = old($name, $this->value);

        if ($this->multiple) {
            if (! is_array($resolved)) {
                $resolved = $resolved === null || $resolved === '' ? [] : [$resolved];
            }
        } else {
            if (is_array($resolved)) {
                $resolved = $resolved[0] ?? null;
            }
            $resolved = $resolved === null || $resolved === '' ? [] : [$resolved];
        }

        return array_values(array_filter(
            array_map(fn ($v) => (string) $v, $resolved),
            fn ($v) => $v !== '',
        ));
    }
}
