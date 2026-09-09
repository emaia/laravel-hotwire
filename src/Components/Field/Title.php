<?php

namespace Emaia\LaravelHotwire\Components\Field;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Field;

class Title extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'div', 'slotName' => Field::SLOTS['title']['name']]);
    }
}
