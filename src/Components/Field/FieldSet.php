<?php

namespace Emaia\LaravelHotwire\Components\Field;

use Illuminate\View\Component;

class FieldSet extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'fieldset', 'slotName' => 'field-set']);
    }
}
