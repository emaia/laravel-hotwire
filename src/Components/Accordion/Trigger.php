<?php

namespace Emaia\LaravelHotwire\Components\Accordion;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class Trigger extends Component
{
    public function __construct(
        public bool $icon = true,
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.accordion-trigger');
    }
}
