<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Contracts\Support\Htmlable;
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
        public bool $multiple = false,
        public string $class = '',
        public string $wrapperClass = '',
        public ?Htmlable $stimulus = null,
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

        $resolvedId = $id ?: ($hasName ? FieldKey::toId($name) : 'hw-file-'.uniqid());
        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $errorId = $resolvedId.'-error';

        // `multiple` posts an array, so the HTML name needs `[]`. The id/errorKey
        //  stays bracket-free (FieldKey strips a trailing `[]`).
        $renderName = $name;
        if ($this->multiple && $hasName && ! str_ends_with($name, '[]')) {
            $renderName = $name.'[]';
        }

        // Per-file rules (e.g. `cover.*`) put failures under sub-keys, so also
        // treat any `errorKey.*` match as an error on this field.
        $hasErrors = $resolvedErrorKey !== ''
            && ($errorsBag->has($resolvedErrorKey) || $errorsBag->has($resolvedErrorKey.'.*'));

        $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;

        $inputController = trim(implode(' ', array_filter([
            'file-preserve',
            $this->resetOnSuccess ? 'reset-files' : null,
        ])));

        // Only wrap when there is something to wrap: the current-file link or a
        // caller-provided wrapper class. Controllers always live on the input.
        $needsWrapper = $this->currentUrl !== null || $this->wrapperClass !== '';

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'renderName' => $renderName,
            'errorId' => $errorId,
            'hasErrors' => $hasErrors,
            'isRequired' => $isRequired,
            'inputController' => $inputController,
            'needsWrapper' => $needsWrapper,
        ];
    }
}
