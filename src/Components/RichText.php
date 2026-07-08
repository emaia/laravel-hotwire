<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class RichText extends Component
{
    use StripsNullProps;

    public string $identifier;

    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public mixed $value = null,
        public ?string $errorKey = null,
        public ?string $placeholder = null,
        public bool $editable = true,
        public string $output = 'html',
        public bool $toolbar = true,
        public bool $imageUpload = false,
        public bool $old = true,
        public string $class = '',
        public string $inputClass = '',
        public string $editorClass = '',
        public string $controller = 'rich-text',
        public ?Htmlable $stimulus = null,
    ) {
        $this->identifier = $this->controller;
    }

    public function render()
    {
        return view('hotwire::component-views.rich-text');
    }

    public function data(): array
    {
        $data = parent::data();
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

        $resolvedId = $id !== null && $id !== ''
            ? $id
            : ($hasName ? FieldKey::toId($name) : 'hw-rich-text-'.uniqid());

        $resolvedErrorKey = $errorKey !== null && $errorKey !== ''
            ? $errorKey
            : ($hasName ? FieldKey::toErrorKey($name) : '');

        $initial = $this->value === null ? '' : (string) $this->value;
        $resolvedValue = $this->old && $resolvedErrorKey !== ''
            ? (string) old($resolvedErrorKey, $initial)
            : $initial;

        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);
        $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'resolvedValue' => $resolvedValue,
            'hasErrors' => $hasErrors,
            'isRequired' => $isRequired,
            'dataController' => $this->identifier,
        ];
    }
}
