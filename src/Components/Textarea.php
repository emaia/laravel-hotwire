<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Textarea extends Component
{
    use StripsNullProps;

    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public mixed $value = null,
        public ?string $errorKey = null,
        public bool $old = true,
        public bool $autoResize = false,
        public ?int $counter = null,
        public bool $countdown = false,
        public string $class = '',
        public string $wrapperClass = '',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.textarea');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['needsWrapper'] = $this->counter !== null;
        $data['internalPrefixes'] = array_values(array_filter([
            $this->counter !== null ? 'data-char-counter-' : null,
        ]));
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

        $resolvedId = $id ?: ($hasName ? FieldKey::toId($name) : 'hwc-textarea-'.uniqid());
        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $errorId = $resolvedId.'-error';

        $resolvedValue = ($this->old && $resolvedErrorKey !== '')
            ? old($resolvedErrorKey, $this->value)
            : $this->value;

        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);
        $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;

        $userController = trim($attributes->get('data-controller', ''));
        $elementController = trim(implode(' ', array_filter([
            $userController,
            $this->autoResize ? 'auto-resize' : null,
        ])));

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $errorId,
            'resolvedValue' => $resolvedValue,
            'hasErrors' => $hasErrors,
            'isRequired' => $isRequired,
            'elementController' => $elementController,
        ];
    }
}
