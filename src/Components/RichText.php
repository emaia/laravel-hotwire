<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
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
        public ?string $content = null,
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
        ViewErrorBag $errorsBag,
        ComponentAttributeBag $attributes,
    ): array {
        $hasName = $name !== null && $name !== '';

        $resolvedId = $id !== null && $id !== ''
            ? $id
            : ($hasName ? FieldKey::toId($name) : 'hwc-rich-text-'.uniqid());

        $resolvedErrorKey = $errorKey !== null && $errorKey !== ''
            ? $errorKey
            : ($hasName ? FieldKey::toErrorKey($name) : '');

        $initial = $this->content ?? '';
        $resolvedValue = $this->old && $resolvedErrorKey !== ''
            ? (string) old($resolvedErrorKey, $initial)
            : $initial;

        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);

        $userController = trim($attributes->get('data-controller', ''));
        $dataController = trim($this->identifier.' '.$userController);

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'resolvedValue' => $resolvedValue,
            'hasErrors' => $hasErrors,
            'dataController' => $dataController,
        ];
    }
}
