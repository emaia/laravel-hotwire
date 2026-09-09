<?php

namespace Emaia\LaravelHotwire\Components\Sheet;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Sheet;

class Header extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'div', 'slotName' => Sheet::SLOTS['header']['name']]);
    }
}
