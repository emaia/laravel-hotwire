<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\AutoSubmit;
use Emaia\LaravelHotwire\Support\FieldKey;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;

class Slider extends Component
{
    use StripsNullProps;

    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public mixed $value = null,
        public int|float|string|null $min = null,
        public int|float|string|null $max = null,
        public int|float|string|null $step = null,
        public string $orientation = 'horizontal',
        public ?string $errorKey = null,
        public bool $old = true,
        public bool|string $autoSubmit = false,
        public int|string|null $autoSubmitDelay = null,
        public string $class = '',
        public ?Htmlable $stimulus = null,
    ) {
        $this->orientation = in_array($this->orientation, ['horizontal', 'vertical'], true)
            ? $this->orientation
            : 'horizontal';
    }

    public function render()
    {
        return view('hotwire::component-views.slider');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['internalPrefixes'] = ['data-slider-'];
        if (AutoSubmit::enabled($this->autoSubmit)) {
            $data['internalPrefixes'][] = 'data-auto-submit-';
        }
        $data['compute'] = $this->computeResolved(...);

        return $this->stripNullProps($data, ['name', 'id', 'errorKey']);
    }

    /** @return array<string, mixed> */
    private function computeResolved(
        ?string $name,
        ?string $id,
        ?string $errorKey,
        ViewErrorBag $errorsBag,
    ): array {
        $hasName = $name !== null && $name !== '';
        $resolvedId = $id ?: ($hasName ? FieldKey::toId($name) : 'hw-slider-'.uniqid());
        $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
        $resolvedValue = ($this->old && $resolvedErrorKey !== '')
            ? old($resolvedErrorKey, $this->value)
            : $this->value;
        $hasErrors = $resolvedErrorKey !== '' && $errorsBag->has($resolvedErrorKey);

        return [
            'resolvedId' => $resolvedId,
            'resolvedErrorKey' => $resolvedErrorKey,
            'errorId' => $resolvedId.'-error',
            'resolvedValue' => $resolvedValue,
            'hasErrors' => $hasErrors,
            'fillPercent' => $this->fillPercent($resolvedValue),
            'elementAction' => trim(implode(' ', array_filter([
                'input->slider#update',
                AutoSubmit::action($this->autoSubmit, 'input', 'debounced'),
            ]))),
            'autoSubmitDelayParam' => AutoSubmit::delayParam($this->autoSubmit, $this->autoSubmitDelay, 'debounced'),
        ];
    }

    /**
     * Portion of the track the preset paints as filled. Rendered server-side so
     * the first paint is already correct: controllers load lazily, so leaving it
     * to the controller shows every slider empty until its chunk arrives.
     * Mirrors the clamping in slider_controller.js.
     */
    private function fillPercent(mixed $value): float
    {
        $min = $this->toNumber($this->min) ?? 0.0;
        $max = $this->toNumber($this->max) ?? 100.0;

        if ($max <= $min) {
            return 0.0;
        }

        $current = $this->toNumber($value) ?? (($min + $max) / 2);

        return round(max(0.0, min(100.0, (($current - $min) / ($max - $min)) * 100)), 4);
    }

    private function toNumber(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
