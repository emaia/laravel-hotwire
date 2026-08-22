<?php

namespace Emaia\LaravelHotwire\Components\RadioGroup;

use Emaia\LaravelHotwire\Support\AutoSubmit;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Item extends Component
{
    public function __construct(
        public mixed $value,
        public bool|string|null $checked = false,
        public string $class = '',
        public string $labelClass = '',
        public ?string $name = null,
        public ?string $id = null,
        public ?string $errorKey = null,
        public ?bool $disabled = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.radio-group-item');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['radioGroupItemValue'] = $this->value;
        $data['radioGroupItemChecked'] = $this->checked;
        $data['radioGroupItemClass'] = $this->class;
        $data['radioGroupItemLabelClass'] = $this->labelClass;
        $data['radioGroupItemName'] = $this->name;
        $data['radioGroupItemId'] = $this->id;
        $data['radioGroupItemErrorKey'] = $this->errorKey;
        $data['radioGroupItemDisabled'] = $this->disabled;
        $data['compute'] = $this->computeResolved(...);

        unset(
            $data['value'],
            $data['checked'],
            $data['class'],
            $data['labelClass'],
            $data['name'],
            $data['id'],
            $data['errorKey'],
            $data['disabled'],
        );

        return $data;
    }

    /** @return array<string, mixed> */
    private function computeResolved(
        ?string $name,
        ?string $id,
        ?string $errorKey,
        mixed $selected,
        bool $old,
        bool $radioGroupDisabled,
        bool|string $autoSubmit,
        int|string|null $autoSubmitDelay,
        ViewErrorBag $errorsBag,
        ComponentAttributeBag $attributes,
    ): array {
        $hasName = $name !== null && $name !== '';
        $baseId = $id ?: ($hasName ? FieldKey::toId($name) : null);
        $valueSlug = Str::slug((string) $this->value);
        $resolvedId = $baseId && $valueSlug !== '' ? $baseId.'-'.$valueSlug : $baseId;
        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $htmlValue = (string) $this->value;
        $isChecked = $this->isCheckedPropTruthy() || (string) $selected === $htmlValue;

        if ($old && $resolvedErrorKey !== '' && session()->hasOldInput()) {
            $oldVal = session()->getOldInput($resolvedErrorKey);
            $isChecked = (string) $oldVal === $htmlValue;
        }

        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);

        return [
            'name' => $name,
            'resolvedId' => $resolvedId,
            'errorId' => $baseId ? $baseId.'-error' : '',
            'isChecked' => $isChecked,
            'isDisabled' => $this->disabled ?? $radioGroupDisabled,
            'hasErrors' => $hasErrors,
            'elementAction' => AutoSubmit::action($autoSubmit, 'change', 'submit'),
            'autoSubmitDelayParam' => AutoSubmit::delayParam($autoSubmit, $autoSubmitDelay, 'submit'),
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
