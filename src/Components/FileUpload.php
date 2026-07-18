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
        'removed',
        'removeFile',
        'fileTooBig',
        'invalidFileType',
        'maxFilesExceeded',
    ];

    public string $identifier;

    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public ?string $errorKey = null,
        public ?string $url = null,
        public ?string $accept = null,
        public ?int $maxSizeBytes = null,
        public ?int $maxFiles = null,
        public bool $multiple = false,
        public bool $preview = true,
        public bool $emitHidden = true,
        public string $paramName = 'file',
        public string $responseKey = 'token',
        public ?string $deleteUrl = null,
        public int $parallelUploads = 3,
        public bool $turboStream = false,
        public mixed $value = null,
        public ?array $messages = null,
        public string $class = '',
        public string $controller = 'file-upload',
        public ?Htmlable $stimulus = null,
    ) {
        if ($url === null || $url === '') {
            throw new InvalidArgumentException('hw:file-upload requires a `url` prop.');
        }

        if (! preg_match('/^[a-z0-9][a-z0-9_-]*(?:--[a-z0-9][a-z0-9_-]*)*$/', $controller)) {
            throw new InvalidArgumentException('Invalid file-upload controller identifier.');
        }

        foreach ($messages ?? [] as $key => $_value) {
            if (! in_array($key, self::MESSAGE_KEYS, true)) {
                $supported = implode(', ', self::MESSAGE_KEYS);
                throw new InvalidArgumentException(
                    "Unknown file-upload message key [{$key}]. Supported keys: {$supported}. ".
                    'Use one of the native message keys.'
                );
            }
        }

        $this->accept = $this->normalizeAccept($accept);
        $this->identifier = $this->controller;
    }

    public function render()
    {
        return view('hotwire::component-views.file-upload');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['internalPrefixes'] = [
            "data-{$this->identifier}-url-",
            "data-{$this->identifier}-hidden-name-",
            "data-{$this->identifier}-accept-",
            "data-{$this->identifier}-max-size-bytes-",
            "data-{$this->identifier}-max-files-",
            "data-{$this->identifier}-multiple-",
            "data-{$this->identifier}-preview-",
            "data-{$this->identifier}-emit-hidden-",
            "data-{$this->identifier}-param-name-",
            "data-{$this->identifier}-response-key-",
            "data-{$this->identifier}-delete-url-",
            "data-{$this->identifier}-parallel-uploads-",
            "data-{$this->identifier}-turbo-stream-",
            "data-{$this->identifier}-messages-",
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

        $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;

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
            'mergedController' => $this->identifier,
            'initialValues' => $initialValues,
            'messagesJson' => $this->resolveMessagesJson(),
        ];
    }

    private function resolveMessagesJson(): ?string
    {
        if (($this->messages ?? []) === []) {
            return null;
        }

        return json_encode($this->messages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
