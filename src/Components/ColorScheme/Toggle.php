<?php

namespace Emaia\LaravelHotwire\Components\ColorScheme;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class Toggle extends Component
{
    public function __construct(
        public string $variant = 'outline',
        public string $size = 'icon',
        public string $modes = 'light dark system',
        public string $storageKey = 'hotwire.colorScheme',
        public string $default = 'system',
        public ?string $tooltip = null,
        public ?string $tooltipSide = null,
        public ?string $tooltipAlign = null,
        public ?string $tooltipEnabledWhen = null,
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.color-scheme-toggle');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['toggleController'] = $this->toggleController();
        $data['hasTooltip'] = $this->hasTooltip();
        $data['protectedPrefixes'] = $this->protectedPrefixes();

        return $data;
    }

    private function toggleController(): string
    {
        return $this->hasTooltip() ? 'color-scheme tooltip' : 'color-scheme';
    }

    private function hasTooltip(): bool
    {
        return $this->tooltip !== null;
    }

    /** @return string[] */
    private function protectedPrefixes(): array
    {
        if ($this->hasTooltip()) {
            return ['data-color-scheme-', 'data-tooltip-'];
        }

        return ['data-color-scheme-'];
    }
}
