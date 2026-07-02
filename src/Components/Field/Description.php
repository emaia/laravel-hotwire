<?php

namespace Emaia\LaravelHotwire\Components\Field;

use Illuminate\View\Component;

class Description extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'p', 'slotName' => 'field-description']);
    }
}
