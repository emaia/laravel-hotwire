<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Illuminate\Contracts\Support\Htmlable;

class ScrollProgress extends Component
{
    public function __construct(
        public int $throttleDelay = 15,
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.scroll-progress');
    }
}
