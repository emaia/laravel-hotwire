<?php

namespace Emaia\LaravelHotwire\Components\Sheet;

use Illuminate\View\Component;

class Header extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'div', 'slotName' => 'sheet-header']);
    }
}
