<?php

namespace Emaia\LaravelHotwire\Components\Accordion;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Illuminate\Contracts\Support\Htmlable;

class Content extends Component
{
    public function __construct(
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.accordion-content');
    }
}
