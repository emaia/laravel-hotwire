<?php

namespace Emaia\LaravelHotwire\Components\Drawer;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Drawer;

class Title extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'h2', 'slotName' => Drawer::SLOTS['title']['name']]);
    }
}
