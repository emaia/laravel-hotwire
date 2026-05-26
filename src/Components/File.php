<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class File extends Component
{
    use StripsNullProps;

    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public ?string $errorKey = null,
        public ?string $currentUrl = null,
        public ?string $currentLabel = null,
        public bool $resetOnSuccess = false,
        public string $class = '',
        public string $wrapperClass = '',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.file');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['internalPrefixes'] = ['data-file-preserve-', 'data-reset-files-'];
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

        $resolvedId = $id ?: ($hasName ? FieldKey::toId($name) : 'hwc-file-'.uniqid());
        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $errorId = $resolvedId.'-error';

        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);
        $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;

        $userController = trim($attributes->get('data-controller', ''));
        $wrapperController = trim(implode(' ', array_filter([
            $userController,
            'file-preserve',
            $this->resetOnSuccess ? 'reset-files' : null,
        ])));

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $errorId,
            'hasErrors' => $hasErrors,
            'isRequired' => $isRequired,
            'wrapperController' => $wrapperController,
        ];
    }
}
