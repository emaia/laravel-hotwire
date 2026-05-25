<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Emaia\LaravelHotwire\Support\MaskPresets;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Input extends Component
{
    use StripsNullProps;

    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public string $type = 'text',
        public mixed $value = null,
        public bool $checked = false,
        public ?string $errorKey = null,
        public bool $old = true,
        public bool $clearable = false,
        public bool $autoSelect = false,
        public ?string $mask = null,
        public string $class = '',
        public string $wrapperClass = '',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.input');
    }

    public function data(): array
    {
        $isCheckable = $this->isCheckable();

        if ($isCheckable) {
            $this->autoSelect = false;
            $this->mask = null;
            $this->clearable = false;
        }

        $data = parent::data();
        $data['isCheckable'] = $isCheckable;
        $data['resolvedMask'] = $this->mask !== null ? MaskPresets::resolve($this->mask) : null;
        $data['internalPrefixes'] = array_values(array_filter([
            $this->clearable ? 'data-clear-input-' : null,
            $this->mask !== null ? 'data-input-mask-' : null,
        ]));

        $data['compute'] = $this->computeResolved(...);

        return $this->stripNullProps($data, ['name', 'id', 'errorKey']);
    }

    /**
     * Computes values that depend on @aware-resolved props and the template-scoped
     * attribute bag. Called from the template via the $compute closure.
     *
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
        $isCheckable = $this->isCheckable();
        $hasName = $name !== null && $name !== '';

        $baseId = $id ?: ($hasName ? FieldKey::toId($name) : 'hwc-input-'.uniqid());
        $resolvedId = $baseId;

        $isGroupInput = $isCheckable
            && $this->value !== null
            && $this->value !== ''
            && ($this->type === 'radio' || ($hasName && str_ends_with($name, '[]')));

        if ($isGroupInput && $id === null) {
            $valueSlug = Str::slug((string) $this->value);
            if ($valueSlug !== '') {
                $resolvedId = $baseId.'-'.$valueSlug;
            }
        }

        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $errorId = $baseId.'-error';

        if ($isCheckable) {
            $resolvedValue = $this->value;
            $isChecked = $this->checked;

            if ($this->old && $resolvedErrorKey !== '' && session()->hasOldInput()) {
                $oldVal = session()->getOldInput($resolvedErrorKey);
                $htmlValue = (string) ($this->value ?? 'on');

                $isChecked = match (true) {
                    $this->type === 'radio' => (string) $oldVal === $htmlValue,
                    is_array($oldVal) => in_array($htmlValue, array_map('strval', $oldVal), true),
                    $this->value !== null => (string) $oldVal === $htmlValue,
                    default => $oldVal !== null,
                };
            }
        } else {
            $resolvedValue = ($this->old && $resolvedErrorKey !== '')
                ? old($resolvedErrorKey, $this->value)
                : $this->value;
            $isChecked = false;
        }

        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);
        $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;

        $userController = trim($attributes->get('data-controller', ''));
        $elementController = trim(implode(' ', array_filter([
            $userController,
            (! $isCheckable && $this->autoSelect) ? 'auto-select' : null,
            (! $isCheckable && $this->mask !== null) ? 'input-mask' : null,
        ])));

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $errorId,
            'resolvedValue' => $resolvedValue,
            'isChecked' => $isChecked,
            'hasErrors' => $hasErrors,
            'isRequired' => $isRequired,
            'elementController' => $elementController,
        ];
    }

    private function isCheckable(): bool
    {
        return in_array($this->type, ['checkbox', 'radio'], true);
    }
}
