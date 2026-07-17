<?php

namespace Emaia\LaravelHotwire\Components\Dropdown;

use Illuminate\View\Component;

class Content extends Component
{
    public function __construct(
        public string $align = 'start',
        public string $side = 'bottom',
        public int|float|string $sideOffset = 4,
        public int|float|string $alignOffset = 0,
        public string $strategy = 'absolute',
        public bool $flip = true,
        public bool $shift = true,
        public ?string $mobileSide = null,
        public ?string $mobileAlign = null,
        public string $mobileMedia = '(max-width: 767px)',
        public ?string $collapsedSide = null,
        public ?string $collapsedAlign = null,
        public string $collapsedWhen = '[data-slot=sidebar][data-collapsible=icon], [data-slot=sidebar][data-state=collapsed], [data-slot=sidebar-wrapper][data-state=collapsed], [data-sidebar-collapsible=icon][data-state=collapsed]',
        public bool $transition = true,
        public string $width = '',
        public string $menuClass = '',
    ) {
        $this->side = $this->oneOf($this->side, ['top', 'right', 'bottom', 'left'], 'bottom');
        $this->align = $this->oneOf($this->align, ['start', 'center', 'end'], 'start');
        $this->mobileSide = $this->mobileSide === null ? null : $this->oneOf($this->mobileSide, ['top', 'right', 'bottom', 'left'], 'bottom');
        $this->mobileAlign = $this->mobileAlign === null ? null : $this->oneOf($this->mobileAlign, ['start', 'center', 'end'], 'start');
        $this->collapsedSide = $this->collapsedSide === null ? null : $this->oneOf($this->collapsedSide, ['top', 'right', 'bottom', 'left'], 'right');
        $this->collapsedAlign = $this->collapsedAlign === null ? null : $this->oneOf($this->collapsedAlign, ['start', 'center', 'end'], 'start');
        $this->strategy = $this->oneOf($this->strategy, ['absolute', 'fixed'], 'absolute');
        $this->sideOffset = $this->number($this->sideOffset, 4);
        $this->alignOffset = $this->number($this->alignOffset, 0);
    }

    public function render()
    {
        return view('hotwire::component-views.dropdown-content');
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
