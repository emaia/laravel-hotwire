<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class HoverCard extends Component
{
    public function __construct(
        public string $id = '',
        public string $align = 'start',
        public string $side = 'bottom',
        public int|float|string $sideOffset = 4,
        public int|float|string $alignOffset = 0,
        public string $strategy = 'fixed',
        public bool $flip = true,
        public bool $shift = true,
        public int|string $openDelay = 10,
        public int|string $closeDelay = 100,
        public bool $open = false,
        public bool $transition = true,
        public ?Htmlable $stimulus = null,
    ) {
        if ($this->id === '') {
            $this->id = uniqid('hover-card-');
        }

        $this->side = $this->oneOf($this->side, ['top', 'right', 'bottom', 'left'], 'bottom');
        $this->align = $this->oneOf($this->align, ['start', 'center', 'end'], 'start');
        $this->strategy = $this->oneOf($this->strategy, ['absolute', 'fixed'], 'fixed');
        $this->sideOffset = $this->number($this->sideOffset, 4);
        $this->alignOffset = $this->number($this->alignOffset, 0);
        $this->openDelay = $this->delay($this->openDelay, 10);
        $this->closeDelay = $this->delay($this->closeDelay, 100);
    }

    public function render()
    {
        return view('hotwire::component-views.hover-card');
    }

    /** @param  string[]  $allowed */
    private function oneOf(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function number(int|float|string $value, int|float $default): string
    {
        if (! is_numeric($value)) {
            return (string) $default;
        }

        $formatted = rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');

        return $formatted === '-0' || $formatted === '' ? '0' : $formatted;
    }

    private function delay(int|string $value, int $default): string
    {
        if (! is_numeric($value)) {
            return (string) $default;
        }

        return (string) max(0, (int) $value);
    }
}
