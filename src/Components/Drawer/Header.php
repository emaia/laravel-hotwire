<?php

namespace Emaia\LaravelHotwire\Components\Drawer;

use Illuminate\View\Component;

class Header extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'div', 'slotName' => 'drawer-header']);
    }
}
