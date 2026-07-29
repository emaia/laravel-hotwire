<?php

namespace Emaia\LaravelHotwire\Components\HoverCard;

use Illuminate\View\Component;

class Content extends Component
{
    public function __construct(
        public string $motion = 'default',
    ) {
        $this->motion = in_array($this->motion, ['default', 'none'], true) ? $this->motion : 'default';
    }

    public function render()
    {
        return view('hotwire::component-views.hover-card-content');
    }
}
