<?php

namespace Emaia\LaravelHotwire\Components\Drawer;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Drawer;

class Footer extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'div', 'slotName' => Drawer::SLOTS['footer']['name']]);
    }
}
