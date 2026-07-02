<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class FieldGroup extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'div', 'slotName' => 'field-group']);
    }
}
