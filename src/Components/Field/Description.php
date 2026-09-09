<?php

namespace Emaia\LaravelHotwire\Components\Field;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Field;

class Description extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'p', 'slotName' => Field::SLOTS['description']['name']]);
    }
}
