<?php

namespace Emaia\LaravelHotwire\Components\Drawer;

use Illuminate\View\Component;

class Close extends Component
{
    public function render()
    {
        return view('hotwire::component-views.drawer-close');
    }
}
