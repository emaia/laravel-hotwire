<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Emaia\LaravelHotwire\Support\AutoSubmit;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Toggle extends Component
{
    use StripsNullProps;

    public function __construct(
        public ?string $name = null,
        public mixed $value = null,
        public bool|string|null $pressed = false,
        public string $variant = 'default',
        public string $size = 'default',
        public string $type = 'button',
        public bool|string $autoSubmit = false,
        public int|string|null $autoSubmitDelay = null,
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.toggle');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $this->stripNullProps($data, ['name']);
    }

    /** @return array<string, mixed> */
    private function computeResolved(?string $name, ComponentAttributeBag $attributes): array
    {
        $isPressed = $this->isPressedPropTruthy();
        $isDisabled = $attributes->has('disabled') && $attributes->get('disabled') !== false;
        $hasName = $name !== null && $name !== '';
        $htmlValue = (string) ($this->value ?? 'on');

        return [
            'htmlValue' => $htmlValue,
            'inputId' => $hasName ? 'hw-toggle-input-'.uniqid() : null,
            'isDisabled' => $isDisabled,
            'isPressed' => $isPressed,
            'state' => $isPressed ? 'on' : 'off',
            'elementAction' => trim(implode(' ', array_filter([
                'click->toggle#toggle',
                AutoSubmit::action($this->autoSubmit, 'change', 'submit'),
            ]))),
            'autoSubmitDelayParam' => AutoSubmit::delayParam($this->autoSubmit, $this->autoSubmitDelay, 'submit'),
            'hiddenDisabled' => $isDisabled || ! $isPressed,
        ];
    }

    private function isPressedPropTruthy(): bool
    {
        if (is_bool($this->pressed)) {
            return $this->pressed;
        }

        return filter_var($this->pressed, FILTER_VALIDATE_BOOLEAN);
    }
}
