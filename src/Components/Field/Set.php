<?php

namespace Emaia\LaravelHotwire\Components\Field;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Field;

class Set extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'fieldset', 'slotName' => Field::SLOTS['set']['name']]);
    }
}
