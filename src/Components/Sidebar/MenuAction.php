<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Sidebar;

class MenuAction extends Component
{
    public function __construct(
        public bool $showOnHover = false,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.sidebar-menu-action', [
            'slotName' => Sidebar::SLOTS['menu-action']['name'],
        ]);
    }
}
