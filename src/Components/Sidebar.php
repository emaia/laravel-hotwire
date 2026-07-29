<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Sidebar extends Component
{
    public function __construct(
        public string $side = 'left',
        public string $variant = 'sidebar',
        public string $collapsible = 'offcanvas',
        public string $motion = 'default',
    ) {
        $this->motion = in_array($this->motion, ['default', 'none'], true) ? $this->motion : 'default';
    }

    public function render()
    {
        return view('hotwire::component-views.sidebar');
    }
}
