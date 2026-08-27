<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\FieldContext;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class Popover extends Component
{
    public function __construct(
        public string|object $id = '',
        public string $align = 'start',
        public string $side = 'bottom',
        public int|float|string $sideOffset = 4,
        public int|float|string $alignOffset = 0,
        public string $strategy = 'fixed',
        public bool $flip = true,
        public bool $shift = true,
        public bool $open = false,
        public ?Htmlable $stimulus = null,
    ) {
        $this->id = app(ComponentId::class)->resolve($this->id, 'hw-popover', 'popover');

        $this->side = $this->oneOf($this->side, ['top', 'right', 'bottom', 'left'], 'bottom');
        $this->align = $this->oneOf($this->align, ['start', 'center', 'end'], 'start');
        $this->strategy = $this->oneOf($this->strategy, ['absolute', 'fixed'], 'fixed');
        $this->sideOffset = $this->number($this->sideOffset, 4);
        $this->alignOffset = $this->number($this->alignOffset, 0);
    }

    public function render()
    {
        return view('hotwire::component-views.popover');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();

        $data['popoverId'] = $this->id;
        $data['popoverAlign'] = $this->align;
        $data['popoverSide'] = $this->side;
        $data['popoverSideOffset'] = $this->sideOffset;
        $data['popoverAlignOffset'] = $this->alignOffset;
        $data['popoverStrategy'] = $this->strategy;
        $data['popoverFlip'] = $this->flip;
        $data['popoverShift'] = $this->shift;
        $data['popoverOpen'] = $this->open;
        $data['popoverStimulus'] = $this->stimulus;
        $data = array_replace($data, FieldContext::boundaryData());

        unset(
            $data['id'],
            $data['align'],
            $data['side'],
            $data['sideOffset'],
            $data['alignOffset'],
            $data['strategy'],
            $data['flip'],
            $data['shift'],
            $data['open'],
            $data['stimulus'],
        );

        return $data;
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
}
