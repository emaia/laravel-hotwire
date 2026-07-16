<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\AutoSubmit;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class ToggleGroup extends Component
{
    use StripsNullProps;

    /** @var string[] */
    public array $selected;

    public bool|string $groupDisabled;

    public function __construct(
        public ?string $name = null,
        public mixed $value = null,
        public string $type = 'multiple',
        public string $orientation = 'horizontal',
        public string $variant = 'default',
        public string $size = 'default',
        public bool|string $disabled = false,
        public bool|string $connected = false,
        public bool $old = true,
        public ?string $id = null,
        public ?string $errorKey = null,
        public bool|string $autoSubmit = false,
        public int|string|null $autoSubmitDelay = null,
        public ?Htmlable $stimulus = null,
    ) {
        $this->type = in_array($type, ['single', 'multiple'], true) ? $type : 'multiple';
        $this->selected = $this->normalizeSelected($value, $this->type);
        $this->groupDisabled = $disabled;
    }

    public function render()
    {
        return view('hotwire::component-views.toggle-group');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['internalPrefixes'] = ['data-toggle-group-'];

        if (AutoSubmit::enabled($this->autoSubmit)) {
            $data['internalPrefixes'][] = 'data-auto-submit-';
        }

        $data['compute'] = $this->computeResolved(...);

        return $this->stripNullProps($data, ['name', 'id', 'errorKey']);
    }

    /** @return array<string, mixed> */
    private function computeResolved(ComponentAttributeBag $attributes): array
    {
        $isDisabled = $this->isTruthy($this->disabled)
            || ($attributes->has('disabled') && $attributes->get('disabled') !== false);

        return [
            'isDisabled' => $isDisabled,
            'isConnected' => $this->isTruthy($this->connected),
            'elementController' => 'toggle-group',
            'elementAction' => trim(implode(' ', array_filter([
                'change->toggle-group#sync',
                AutoSubmit::action($this->autoSubmit, 'change', 'submit'),
            ]))),
            'autoSubmitDelayParam' => AutoSubmit::delayParam($this->autoSubmit, $this->autoSubmitDelay, 'submit'),
        ];
    }

    /** @return string[] */
    private function normalizeSelected(mixed $value, string $type): array
    {
        $values = is_array($value) ? $value : ($value !== null ? [$value] : []);
        $values = array_values(array_map(static fn (mixed $item): string => (string) $item, $values));

        return $type === 'single' ? array_slice($values, 0, 1) : $values;
    }

    private function isTruthy(bool|string|null $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
