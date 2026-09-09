<?php

namespace Emaia\LaravelHotwire\Components\Dropdown;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Dropdown;

class Group extends Component
{
    public function render()
    {
        return view('hotwire::component-views.dropdown-group', [
            'slotName' => Dropdown::SLOTS['group']['name'],
        ]);
    }
}
