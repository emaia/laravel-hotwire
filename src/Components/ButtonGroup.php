<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

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
