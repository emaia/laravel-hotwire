<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Sidebar;

class Separator extends Component
{
    public function render()
    {
        return view('hotwire::component-views.sidebar-separator', [
            'slotName' => Sidebar::SLOTS['separator']['name'],
        ]);
    }
}
