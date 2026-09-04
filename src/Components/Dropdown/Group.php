<?php

namespace Emaia\LaravelHotwire\Components\Dropdown;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Group extends Component
{
    public function render()
    {
        return view('hotwire::component-views.dropdown-group');
    }
}
