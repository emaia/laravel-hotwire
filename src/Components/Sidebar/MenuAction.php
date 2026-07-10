<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Illuminate\View\Component;

class MenuAction extends Component
{
    public function __construct(
        public bool $showOnHover = false,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.sidebar-menu-action');
    }
}
