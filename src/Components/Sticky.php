<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Sticky extends Component
{
    public function __construct(
        public string $side = 'top',
        public string|int|float $offset = 0,
        public bool $surface = true,
        public string $as = 'div',
    ) {
        $this->side = in_array($this->side, ['top', 'bottom'], true) ? $this->side : 'top';
    }

    public function render()
    {
        return view('hotwire::component-views.sticky');
    }
}
