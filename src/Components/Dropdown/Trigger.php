<?php

namespace Emaia\LaravelHotwire\Components\Dropdown;

use Illuminate\View\Component;

class Trigger extends Component
{
    public function __construct(
        public bool $asChild = false,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.dropdown-trigger');
    }
}
