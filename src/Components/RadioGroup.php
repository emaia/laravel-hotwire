<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\AutoSubmit;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class RadioGroup extends Component
{
    use StripsNullProps;

    public bool $radioGroupDisabled;

    /** @param array<int|string, string> $options */
    public function __construct(
        public ?string $name = null,
        public array $options = [],
        public mixed $selected = null,
        public bool $disabled = false,
        public string $orientation = 'vertical',
        public string $class = '',
        public string $wrapperClass = '',
        public string $labelClass = '',
        public bool $old = true,
        public ?string $id = null,
        public ?string $errorKey = null,
        public ?Htmlable $stimulus = null,
        public bool|string $autoSubmit = false,
        public int|string|null $autoSubmitDelay = null,
    ) {
        if ($options !== [] && array_keys($options) === range(0, count($options) - 1)) {
            $this->options = array_combine($options, $options);
        }

        $this->orientation = in_array($this->orientation, ['horizontal', 'vertical'], true)
            ? $this->orientation
            : 'vertical';

        $this->radioGroupDisabled = $this->disabled;
    }

    public function render()
    {
        return view('hotwire::component-views.radio-group');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['internalPrefixes'] = array_filter([
            AutoSubmit::enabled($this->autoSubmit) ? 'data-auto-submit-' : null,
        ]);
        $data['compute'] = $this->computeResolved(...);

        return $this->stripNullProps($data, ['name', 'id', 'errorKey']);
    }

    /** @return array<string, mixed> */
    private function computeResolved(
        ?string $name,
        ?string $id,
        ?string $errorKey,
        ViewErrorBag $errorsBag,
        ComponentAttributeBag $attributes,
    ): array {
        $hasName = $name !== null && $name !== '';
        $baseId = $id ?: ($hasName ? FieldKey::toId($name) : null);
        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $errorId = $baseId ? $baseId.'-error' : '';
        $resolvedSelected = $this->old && $resolvedErrorKey !== ''
            ? old($resolvedErrorKey, $this->selected)
            : $this->selected;
        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);

        return [
            'name' => $name,
            'baseId' => $baseId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $errorId,
            'resolvedSelected' => $resolvedSelected,
            'hasErrors' => $hasErrors,
            'elementAction' => AutoSubmit::action($this->autoSubmit, 'change', 'submit'),
            'autoSubmitDelayParam' => AutoSubmit::delayParam($this->autoSubmit, $this->autoSubmitDelay, 'submit'),
        ];
    }
}
