<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class ButtonGroup extends Component
{
    public function __construct(
        public string $orientation = 'horizontal',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.button-group');
    }
}
