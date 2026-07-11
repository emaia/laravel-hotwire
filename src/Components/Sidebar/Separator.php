<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Illuminate\View\Component;

class Separator extends Component
{
    public function render()
    {
        return view('hotwire::component-views.sidebar-separator');
    }
}
