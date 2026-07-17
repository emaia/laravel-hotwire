<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Navbar extends Component
{
    public function __construct(
        public string $variant = 'line',
        public string $orientation = 'horizontal',
        public string $overflow = 'scroll',
        public bool $sticky = false,
        public string $stickySide = 'top',
        public string|int|float $stickyOffset = 0,
    ) {
        $this->orientation = in_array($this->orientation, ['horizontal', 'vertical'], true) ? $this->orientation : 'horizontal';
        $this->stickySide = in_array($this->stickySide, ['top', 'bottom'], true) ? $this->stickySide : 'top';
    }

    public function render()
    {
        return view('hotwire::component-views.navbar');
    }
}
