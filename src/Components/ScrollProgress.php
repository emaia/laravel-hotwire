<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class ScrollProgress extends Component
{
    public function __construct(
        public int $throttleDelay = 15,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.scroll-progress');
    }
}
