<?php

namespace Emaia\LaravelHotwire\Components\Dropdown;

use Illuminate\View\Component;

class Separator extends Component
{
    public function render()
    {
        return view('hotwire::component-views.dropdown-separator');
    }
}
