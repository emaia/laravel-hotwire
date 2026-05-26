<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Select extends Component
{
    use StripsNullProps;

    /** @param  array<int|string, string>  $options */
    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public array $options = [],
        public mixed $selected = null,
        public ?string $errorKey = null,
        public bool $old = true,
        public ?string $placeholder = null,
        public bool $nullable = false,
        public string $class = '',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.select');
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

        $resolvedId = $id ?: ($hasName ? FieldKey::toId($name) : 'hwc-select-'.uniqid());
        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $errorId = $resolvedId.'-error';

        $resolvedSelected = ($this->old && $resolvedErrorKey !== '')
            ? old($resolvedErrorKey, $this->selected)
            : $this->selected;

        $isMultiple = $attributes->has('multiple') && $attributes->get('multiple') !== false;

        if ($isMultiple) {
            $resolvedSelected = match (true) {
                is_array($resolvedSelected) => $resolvedSelected,
                $resolvedSelected === null || $resolvedSelected === '' => [],
                default => [$resolvedSelected],
            };
            $selectedSet = array_map('strval', $resolvedSelected);
            $placeholderSelected = false;
        } else {
            $selectedSet = [];
            $placeholderSelected = $resolvedSelected === '' || $resolvedSelected === null;
        }

        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);
        $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $errorId,
            'resolvedSelected' => $resolvedSelected,
            'isMultiple' => $isMultiple,
            'selectedSet' => $selectedSet,
            'placeholderSelected' => $placeholderSelected,
            'hasErrors' => $hasErrors,
            'isRequired' => $isRequired,
        ];
    }
}
