<?php

namespace Emaia\LaravelHotwire\Components\ToggleGroup;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Item extends Component
{
    use StripsNullProps;

    public function __construct(
        public mixed $value,
        public bool|string|null $pressed = null,
        public bool|string|null $disabled = null,
        public ?string $name = null,
        public ?string $id = null,
        public ?string $errorKey = null,
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.toggle-group-item');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $this->stripNullProps($data, ['name', 'id', 'errorKey']);
    }

    /**
     * @param  string[]  $selected
     * @return array<string, mixed>
     */
    private function computeResolved(
        ?string $name,
        string $type,
        array $selected,
        bool $old,
        ?string $id,
        ?string $errorKey,
        string $variant,
        string $size,
        bool|string|null $groupDisabled,
        ViewErrorBag $errorsBag,
        ComponentAttributeBag $attributes,
    ): array {
        $type = in_array($type, ['single', 'multiple'], true) ? $type : 'multiple';
        $hasName = $name !== null && $name !== '';

        if ($hasName && $type === 'multiple' && ! str_ends_with($name, '[]')) {
            $name = $name.'[]';
        }

        $baseId = $id ?: ($hasName ? FieldKey::toId($name) : null);
        $valueSlug = Str::slug((string) $this->value);
        $inputId = $baseId && $valueSlug !== '' ? $baseId.'-'.$valueSlug.'-input' : null;
        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $selectedValues = $this->oldSelectedValues($selected, $resolvedErrorKey, $old);
        $htmlValue = (string) $this->value;
        $isPressed = $this->isPressedPropTruthy()
            || in_array($htmlValue, array_map('strval', $selectedValues), true);

        $isDisabled = $this->isTruthy($groupDisabled)
            || $this->isTruthy($this->disabled)
            || ($attributes->has('disabled') && $attributes->get('disabled') !== false);
        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);

        return [
            'name' => $name,
            'htmlValue' => $htmlValue,
            'inputId' => $inputId,
            'errorId' => $baseId ? $baseId.'-error' : null,
            'isPressed' => $isPressed,
            'state' => $isPressed ? 'on' : 'off',
            'isDisabled' => $isDisabled,
            'hiddenDisabled' => $isDisabled || ! $isPressed,
            'hasErrors' => $hasErrors,
            'variant' => $variant,
            'size' => $size,
        ];
    }

    /**
     * @param  string[]  $selected
     * @return string[]
     */
    private function oldSelectedValues(array $selected, string $errorKey, bool $old): array
    {
        if (! $old || $errorKey === '' || ! session()->hasOldInput()) {
            return $selected;
        }

        $oldValue = session()->getOldInput($errorKey);
        $oldValues = is_array($oldValue) ? $oldValue : ($oldValue !== null ? [$oldValue] : []);

        return array_values(array_map(static fn (mixed $item): string => (string) $item, $oldValues));
    }

    private function isPressedPropTruthy(): bool
    {
        if (is_bool($this->pressed)) {
            return $this->pressed;
        }

        return filter_var($this->pressed, FILTER_VALIDATE_BOOLEAN);
    }

    private function isTruthy(bool|string|null $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
