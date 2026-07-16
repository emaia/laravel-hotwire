<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class Button extends Component
{
    public function __construct(
        public string $variant = 'default',
        public string $size = 'default',
        public string $type = 'button',
        public string $as = 'button',
        public string $slotName = 'button',
        public ?string $frame = null,
        public ?string $hotkey = null,
        public ?string $tooltip = null,
        public ?string $tooltipSide = null,
        public ?string $tooltipAlign = null,
        public ?string $tooltipEnabledWhen = null,
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.button');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['buttonController'] = $this->buttonController();
        $data['buttonAction'] = $this->buttonAction();
        $data['buttonProtectedPrefixes'] = $this->buttonProtectedPrefixes();
        $data['hasTooltip'] = $this->hasTooltip();

        return $data;
    }

    private function buttonController(): ?string
    {
        $controllers = array_filter([
            $this->hasHotkey() ? 'hotkey' : null,
            $this->hasTooltip() ? 'tooltip' : null,
        ]);

        return $controllers === [] ? null : implode(' ', $controllers);
    }

    private function buttonAction(): ?string
    {
        $actions = $this->hotkeyActions();

        return $actions === [] ? null : implode(' ', $actions);
    }

    /** @return string[] */
    private function hotkeyActions(): array
    {
        if (! $this->hasHotkey()) {
            return [];
        }

        return array_map(
            static fn (string $hotkey): string => 'keydown.'.$hotkey.'@window->hotkey#click',
            array_map($this->normalizeHotkey(...), preg_split('/\s+/', trim($this->hotkey)) ?: [])
        );
    }

    /** @return string[] */
    private function buttonProtectedPrefixes(): array
    {
        return array_values(array_filter([
            $this->hasHotkey() ? 'data-hotkey-' : null,
            $this->hasTooltip() ? 'data-tooltip-' : null,
        ]));
    }

    private function hasHotkey(): bool
    {
        return $this->hotkey !== null && trim($this->hotkey) !== '';
    }

    private function hasTooltip(): bool
    {
        return $this->tooltip !== null;
    }

    private function normalizeHotkey(string $hotkey): string
    {
        return implode('+', array_map(
            static fn (string $part): string => strtolower($part) === 'cmd' ? 'meta' : strtolower($part),
            explode('+', $hotkey)
        ));
    }
}
