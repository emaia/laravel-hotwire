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
        public string $class = '',
        public string $controller = 'file-upload',
    ) {
        if ($url === null || $url === '') {
            throw new InvalidArgumentException('x-hwc::file-upload requires a `url` prop.');
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

        $resolvedId = $id ?: ($hasName ? FieldKey::toId($name) : 'hwc-file-upload-'.uniqid());
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

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $errorId,
            'hiddenName' => $hiddenName,
            'hasErrors' => $hasErrors,
            'isRequired' => $isRequired,
            'mergedController' => $mergedController,
        ];
    }
}
