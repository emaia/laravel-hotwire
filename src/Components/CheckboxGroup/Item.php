<?php

namespace Emaia\LaravelHotwire\Components\CheckboxGroup;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\AutoSubmit;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Item extends Component
{
    use StripsNullProps;

    public function __construct(
        public mixed $value,
        public bool|string|null $checked = false,
        public string $class = '',
        public string $labelClass = '',
        public ?string $name = null,
        public ?string $id = null,
        public ?string $errorKey = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.checkbox-group-item');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $this->stripNullProps($data, ['name', 'id', 'errorKey']);
    }

    /**
     * @param  array<int, mixed>  $selected
     * @return array<string, mixed>
     */
    private function computeResolved(
        ?string $name,
        ?string $id,
        ?string $errorKey,
        array $selected,
        bool $old,
        bool $selectAll,
        bool|string $autoSubmit,
        int|string|null $autoSubmitDelay,
        ViewErrorBag $errorsBag,
        ComponentAttributeBag $attributes,
    ): array {
        $hasName = $name !== null && $name !== '';

        if ($hasName && ! str_ends_with($name, '[]')) {
            $name = $name.'[]';
        }

        $baseId = $id ?: ($hasName ? FieldKey::toId($name) : null);
        $valueSlug = Str::slug((string) $this->value);
        $resolvedId = $baseId && $valueSlug !== '' ? $baseId.'-'.$valueSlug : $baseId;
        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $htmlValue = (string) $this->value;
        $isChecked = $this->isCheckedPropTruthy()
            || in_array($htmlValue, array_map('strval', $selected), true);

        if ($old && $resolvedErrorKey !== '' && session()->hasOldInput()) {
            $oldVal = session()->getOldInput($resolvedErrorKey);
            $oldValues = is_array($oldVal) ? $oldVal : ($oldVal !== null ? [$oldVal] : []);

            $isChecked = in_array($htmlValue, array_map('strval', $oldValues), true);
        }

        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);

        return [
            'name' => $name,
            'resolvedId' => $resolvedId,
            'errorId' => $baseId ? $baseId.'-error' : '',
            'isChecked' => $isChecked,
            'hasErrors' => $hasErrors,
            'selectAllTarget' => $selectAll ? 'checkbox' : null,
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
