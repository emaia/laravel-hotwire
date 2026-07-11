<?php

namespace Emaia\LaravelHotwire\Components\Sheet;

use Illuminate\View\Component;

class Title extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'h2', 'slotName' => 'sheet-title']);
    }
}
