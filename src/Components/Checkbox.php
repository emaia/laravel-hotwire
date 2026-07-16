<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\AutoSubmit;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Checkbox extends Component
{
    use StripsNullProps;

    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public mixed $value = null,
        public bool|string|null $checked = false,
        public ?string $errorKey = null,
        public bool $old = true,
        public ?string $uncheckedValue = null,
        public bool $indeterminate = false,
        public bool|string $autoSubmit = false,
        public int|string|null $autoSubmitDelay = null,
        public string $class = '',
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.checkbox');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['internalPrefixes'] = array_values(array_filter([
            $this->indeterminate ? 'data-checkbox-' : null,
            AutoSubmit::enabled($this->autoSubmit) ? 'data-auto-submit-' : null,
        ]));
        $data['compute'] = $this->computeResolved(...);

        return $this->stripNullProps($data, ['name', 'id', 'errorKey', 'uncheckedValue']);
    }

    /** @return array<string, mixed> */
    private function computeResolved(
        ?string $name,
        ?string $id,
        ?string $errorKey,
        bool $required,
        ViewErrorBag $errorsBag,
        ComponentAttributeBag $attributes,
    ): array {
        $hasName = $name !== null && $name !== '';
        $resolvedId = $id ?: ($hasName ? FieldKey::toId($name) : 'hw-checkbox-'.uniqid());
        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $htmlValue = (string) ($this->value ?? 'on');
        $isChecked = $this->isCheckedPropTruthy();

        if ($this->old && $resolvedErrorKey !== '' && session()->hasOldInput()) {
            $oldVal = session()->getOldInput($resolvedErrorKey);

            $isChecked = match (true) {
                is_array($oldVal) => in_array($htmlValue, array_map('strval', $oldVal), true),
                $this->value !== null || $this->uncheckedValue !== null => (string) $oldVal === $htmlValue,
                default => $oldVal !== null,
            };
        }

        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);
        $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $resolvedId.'-error',
            'isChecked' => $isChecked,
            'hasErrors' => $hasErrors,
            'isRequired' => $isRequired,
            'elementController' => $this->indeterminate ? 'checkbox' : '',
            'elementAction' => AutoSubmit::action($this->autoSubmit, 'change', 'submit'),
            'autoSubmitDelayParam' => AutoSubmit::delayParam($this->autoSubmit, $this->autoSubmitDelay, 'submit'),
            'renderUncheckedValue' => $hasName && $this->uncheckedValue !== null,
            'hiddenDisabled' => $attributes->has('disabled') && $attributes->get('disabled') !== false,
        ];
    }

    private function isCheckedPropTruthy(): bool
    {
        if (is_bool($this->checked)) {
            return $this->checked;
        }

        return filter_var($this->checked, FILTER_VALIDATE_BOOLEAN);
    }
}
