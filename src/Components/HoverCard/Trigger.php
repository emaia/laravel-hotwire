<?php

namespace Emaia\LaravelHotwire\Components\HoverCard;

use Illuminate\View\Component;

class Trigger extends Component
{
    public function __construct(
        public string $as = 'button',
        public string $variant = 'link',
        public string $size = 'default',
        public string $type = 'button',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.hover-card-trigger');
    }
}
