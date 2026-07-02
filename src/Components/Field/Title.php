<?php

namespace Emaia\LaravelHotwire\Components\Field;

use Illuminate\View\Component;

class Title extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'div', 'slotName' => 'field-title']);
    }
}
