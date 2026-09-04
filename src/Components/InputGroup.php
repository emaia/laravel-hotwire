<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class InputGroup extends Component
{
    public function render()
    {
        return view('hotwire::component-views.input-group');
    }
}
