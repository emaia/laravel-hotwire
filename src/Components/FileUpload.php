<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;

class FileUpload extends Component
{
    use StripsNullProps;

    /**
     * Short `:messages` keys → Dropzone `dict*` option keys. The shapes are deliberately
     * the camelCased part of the dict* name (with `Message` suffix dropped for the two
     * keys that have it), so `:messages="['default' => '…', 'fileTooBig' => '…']"` reads
     * naturally. Unknown short keys throw so typos surface early; Dropzone-specific dict
     * additions can always be passed through `:options` directly.
     */
    private const MESSAGE_DICT_MAP = [
        'default' => 'dictDefaultMessage',
        'fallback' => 'dictFallbackMessage',
        'fallbackText' => 'dictFallbackText',
        'fileTooBig' => 'dictFileTooBig',
        'invalidFileType' => 'dictInvalidFileType',
        'responseError' => 'dictResponseError',
        'cancelUpload' => 'dictCancelUpload',
        'cancelUploadConfirmation' => 'dictCancelUploadConfirmation',
        'uploadCanceled' => 'dictUploadCanceled',
        'removeFile' => 'dictRemoveFile',
        'removeFileConfirmation' => 'dictRemoveFileConfirmation',
        'maxFilesExceeded' => 'dictMaxFilesExceeded',
        'fileSizeUnits' => 'dictFileSizeUnits',
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
        public ?array $options = null,
        public string $class = '',
        public string $controller = 'file-upload',
    ) {
        if ($url === null || $url === '') {
            throw new InvalidArgumentException('hw:file-upload requires a `url` prop.');
        }

        foreach ($messages ?? [] as $key => $_value) {
            if (! isset(self::MESSAGE_DICT_MAP[$key])) {
                $supported = implode(', ', array_keys(self::MESSAGE_DICT_MAP));
                throw new InvalidArgumentException(
                    "Unknown file-upload message key [{$key}]. Supported keys: {$supported}. ".
                    'Pass uncommon Dropzone dict* options via `:options` instead.'
                );
            }
        }

        $this->identifier = $this->controller;
    }

    public function render()
    {
        return view('hotwire::component-views.file-upload');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['internalPrefixes'] = ['data-'.$this->identifier.'-'];
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

        $hiddenName = null;
        if ($hasName) {
            $hiddenName = $this->multiple && ! str_ends_with($name, '[]')
                ? $name.'[]'
                : $name;
        }

        $hasErrors = $resolvedErrorKey !== ''
            && ($errorsBag->has($resolvedErrorKey) || $errorsBag->has($resolvedErrorKey.'.*'));

        $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;

        $userController = trim($attributes->get('data-controller', ''));
        $mergedController = trim($userController === '' ? $this->identifier : $userController.' '.$this->identifier);

        $keyActions = "keydown.enter->{$this->identifier}#openPicker keydown.space->{$this->identifier}#openPicker";
        $userAction = trim($attributes->get('data-action', ''));
        $mergedAction = trim($userAction === '' ? $keyActions : $userAction.' '.$keyActions);

        $hasAriaLabel = $attributes->has('aria-label');

        $initialValues = $hasName ? $this->resolveInitialValues($name) : [];

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $errorId,
            'hiddenName' => $hiddenName,
            'hasErrors' => $hasErrors,
            'isRequired' => $isRequired,
            'mergedController' => $mergedController,
            'mergedAction' => $mergedAction,
            'hasAriaLabel' => $hasAriaLabel,
            'initialValues' => $initialValues,
            'optionsJson' => $this->resolveOptionsJson(),
        ];
    }

    /**
     * Build the JSON-encoded Dropzone options bag from the `messages` + `options` props.
     *
     * `messages` short keys (`default`, `fileTooBig`) map to Dropzone's `dict*` form
     * (`dictDefaultMessage`, `dictFileTooBig`). `options` is the escape hatch — it merges
     * last and wins on key collision, so an explicit `dictDefaultMessage` overrides any
     * mapping from `messages`. Returns null when both inputs are empty.
     */
    private function resolveOptionsJson(): ?string
    {
        $dictFromMessages = [];
        foreach ($this->messages ?? [] as $key => $value) {
            $dictFromMessages[self::MESSAGE_DICT_MAP[$key]] = $value;
        }

        $merged = array_merge($dictFromMessages, $this->options ?? []);

        if ($merged === []) {
            return null;
        }

        return json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
