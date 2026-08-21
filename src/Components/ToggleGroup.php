<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\AutoSubmit;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class ToggleGroup extends Component
{
    /** @var string[] */
    public array $selected;

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
    }

    public function render()
    {
        return view('hotwire::component-views.toggle-group');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['toggleGroupContext'] = true;
        $data['toggleGroupName'] = $this->name;
        $data['toggleGroupValue'] = $this->value;
        $data['toggleGroupType'] = $this->type;
        $data['toggleGroupOrientation'] = $this->orientation;
        $data['toggleGroupVariant'] = $this->variant;
        $data['toggleGroupSize'] = $this->size;
        $data['toggleGroupDisabled'] = $this->disabled;
        $data['toggleGroupConnected'] = $this->connected;
        $data['toggleGroupOld'] = $this->old;
        $data['toggleGroupId'] = $this->id;
        $data['toggleGroupErrorKey'] = $this->errorKey;
        $data['toggleGroupAutoSubmit'] = $this->autoSubmit;
        $data['toggleGroupAutoSubmitDelay'] = $this->autoSubmitDelay;
        $data['toggleGroupStimulus'] = $this->stimulus;
        $data['toggleGroupSelected'] = $this->selected;

        // A selection group owns a name, an id base and an error key, so field.label and
        // field.error nested in it must resolve against the group rather than a Field far
        // above. This is deliberately not the fieldName/fieldId/fieldErrorKey protocol:
        // group items end their fallback chain on those keys, and reusing them here would
        // let an outer group's name leak into a nameless inner group. Publish only the
        // keys this group actually owns, so a surrounding Field still passes through.
        if ($this->name !== null && $this->name !== '') {
            $data['fieldOwnerName'] = $this->name;
        }

        if ($this->id !== null && $this->id !== '') {
            $data['fieldOwnerId'] = $this->id;
        }

        if ($this->errorKey !== null && $this->errorKey !== '') {
            $data['fieldOwnerErrorKey'] = $this->errorKey;
        }
        $data['internalPrefixes'] = ['data-toggle-group-'];

        if (AutoSubmit::enabled($this->autoSubmit)) {
            $data['internalPrefixes'][] = 'data-auto-submit-';
        }

        $data['compute'] = $this->computeResolved(...);

        unset(
            $data['name'],
            $data['value'],
            $data['type'],
            $data['orientation'],
            $data['variant'],
            $data['size'],
            $data['disabled'],
            $data['connected'],
            $data['old'],
            $data['id'],
            $data['errorKey'],
            $data['autoSubmit'],
            $data['autoSubmitDelay'],
            $data['stimulus'],
            $data['selected'],
        );

        return $data;
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
