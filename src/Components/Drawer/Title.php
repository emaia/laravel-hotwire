<?php

namespace Emaia\LaravelHotwire\Components\Drawer;

use Illuminate\View\Component;

class Title extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'h2', 'slotName' => 'drawer-title']);
    }
}
