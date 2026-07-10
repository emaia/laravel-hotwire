<?php

namespace Emaia\LaravelHotwire\Components\Drawer;

use Illuminate\View\Component;

class Content extends Component
{
    public function render()
    {
        return view('hotwire::component-views.drawer-content');
    }
}
