<?php

namespace Emaia\LaravelHotwire\Components\Sheet;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Sheet;

class Footer extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'div', 'slotName' => Sheet::SLOTS['footer']['name']]);
    }
}
