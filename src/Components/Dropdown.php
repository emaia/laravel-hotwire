<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class Dropdown extends Component
{
    public function __construct(
        public string $id = '',
        public string $align = 'start',
        public string $side = 'bottom',
        public int|float|string $sideOffset = 4,
        public int|float|string $alignOffset = 0,
        public string $strategy = 'absolute',
        public bool $flip = true,
        public bool $shift = true,
        public bool $open = false,
        public bool $closeOnSelect = true,
        public bool $transition = true,
        public string $triggerClass = '',
        public string $width = '',
        public string $menuClass = '',
        public ?Htmlable $stimulus = null,
    ) {
        if ($this->id === '') {
            $this->id = uniqid('dropdown-');
        }

        $this->side = $this->oneOf($this->side, ['top', 'right', 'bottom', 'left'], 'bottom');
        $this->align = $this->oneOf($this->align, ['start', 'center', 'end'], 'start');
        $this->strategy = $this->oneOf($this->strategy, ['absolute', 'fixed'], 'absolute');
        $this->sideOffset = $this->number($this->sideOffset, 4);
        $this->alignOffset = $this->number($this->alignOffset, 0);
    }

    public function render()
    {
        return view('hotwire::component-views.dropdown');
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $data;
    }

    private function computeResolved(): array
    {
        return ['controller' => 'dropdown'];
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
