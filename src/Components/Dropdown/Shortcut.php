<?php

namespace Emaia\LaravelHotwire\Components\Dropdown;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Dropdown;

class Shortcut extends Component
{
    public function render()
    {
        return view('hotwire::component-views.dropdown-shortcut', [
            'slotName' => Dropdown::SLOTS['shortcut']['name'],
        ]);
    }
}
