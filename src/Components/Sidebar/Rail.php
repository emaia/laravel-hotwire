<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Sidebar;

class Rail extends Component
{
    public function __construct(
        public string $label = 'Toggle Sidebar',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.sidebar-rail', [
            'slotName' => Sidebar::SLOTS['rail']['name'],
        ]);
    }
}
